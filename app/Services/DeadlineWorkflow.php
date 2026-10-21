<?php

namespace App\Services;

use App\Models\Deadline;
use App\Models\DeadlineAttachment;
use App\Models\DeadlineChangeRequest;
use App\Models\DeadlineEvent;
use App\Models\Submission;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\FileStore;
use App\Support\Fmt;
use App\Support\Notifier;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every state transition of a deadline lives here. Each method runs in a transaction on a
 * row-locked deadline, re-validates the current status, and records history, notifications
 * and an audit entry.
 */
class DeadlineWorkflow
{
    private function link(Deadline $d): string
    {
        return "/deadlines/{$d->id}";
    }

    private function lock(Deadline $deadline): Deadline
    {
        return Deadline::query()->whereKey($deadline->getKey())->lockForUpdate()->firstOrFail();
    }

    private function ensure(Deadline $d, array $statuses): void
    {
        abort_unless(in_array($d->status, $statuses, true), 409, 'Դեդլայնի կարգավիճակը փոխվել է։ Թարմացրեք էջը և կրկին փորձեք։');
    }

    private function record(Deadline $d, ?User $user, string $type, array $data = []): void
    {
        DeadlineEvent::query()->create(['deadline_id' => $d->id, 'user_id' => $user?->id, 'type' => $type, 'data' => $data ?: null]);
    }

    private function iso(?\DateTimeInterface $date): ?string
    {
        return $date ? Carbon::instance($date)->utc()->toISOString() : null;
    }

    /* ------------------------------------------------------------------ create */

    /** @param  array<int, UploadedFile>  $files */
    public function create(User $admin, array $data, array $files = []): Deadline
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($admin, $data, $files, &$stored) {
                // An administrator assigning work to themselves needs no negotiation: it starts confirmed.
                $self = (int) $data['assigned_to'] === $admin->id;

                $deadline = Deadline::query()->create([
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'project_id' => $data['project_id'] ?? null,
                    'assigned_to' => $data['assigned_to'],
                    'created_by' => $admin->id,
                    'starts_at' => $data['starts_at'] ?? null,
                    'due_at' => $data['due_at'],
                    'priority' => $data['priority'] ?? 'normal',
                    'status' => $self ? Deadline::CONFIRMED : Deadline::NEW,
                    'confirmed_at' => $self ? now() : null,
                ]);

                $assignee = User::query()->findOrFail($deadline->assigned_to);
                $this->record($deadline, $admin, 'created', [
                    'assigned_to' => $assignee->name,
                    'due_at' => $this->iso($deadline->due_at),
                    'self_assigned' => $self,
                ]);

                foreach ($files as $file) {
                    $stored[] = $this->storeAttachment($deadline, $admin, $file)->path;
                }

                ActivityLogger::log($admin, 'deadline.created', $deadline, "Ստեղծվեց դեդլայն՝ {$deadline->title}");
                ActivityLogger::log($admin, 'deadline.assigned', $deadline, "«{$deadline->title}» → {$assignee->name}", ['assigned_to' => $assignee->id]);

                Notifier::send(
                    $assignee,
                    'deadline.assigned',
                    'Նոր դեդլայն',
                    "«{$deadline->title}»՝ ժամկետ {$this->fmt($deadline)}։ Խնդրում ենք հաստատել կամ առաջարկել փոփոխություն։",
                    $this->link($deadline),
                    ['deadline_id' => $deadline->id],
                    $admin,
                );

                return $deadline;
            });
        } catch (\Throwable $e) {
            FileStore::deleteMany($stored);
            throw $e;
        }
    }

    private function fmt(Deadline $d): string
    {
        return Fmt::dt($d->due_at);
    }

    /* ------------------------------------------------------------------ update */

    public function update(Deadline $deadline, User $admin, array $data): Deadline
    {
        return DB::transaction(function () use ($deadline, $admin, $data) {
            $d = $this->lock($deadline);

            abort_if($d->status === Deadline::COMPLETED, 409, 'Ավարտված դեդլայնը հնարավոր չէ փոփոխել։');

            $newDue = $data['due_at'] ?? null;
            $dueChanged = $newDue !== null && ! $d->due_at->equalTo($newDue);
            $newAssignee = isset($data['assigned_to']) ? (int) $data['assigned_to'] : $d->assigned_to;
            $assigneeChanged = $newAssignee !== $d->assigned_to;

            abort_if(
                $assigneeChanged && in_array($d->status, Deadline::REVIEW_STATUSES, true),
                409,
                'Հանձնված աշխատանքը նախ պետք է ստուգվի, հետո կարելի է փոխել պատասխանատուին։'
            );

            if ($dueChanged && $newDue->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['due_at' => ['Նոր ժամկետը պետք է լինի ապագայում։']]);
            }

            $changes = [];
            foreach (['title', 'project_id', 'priority'] as $field) {
                if (array_key_exists($field, $data) && (string) $d->{$field} !== (string) $data[$field]) {
                    $changes[$field] = ['from' => $d->{$field}, 'to' => $data[$field]];
                }
            }
            if (array_key_exists('description', $data) && (string) $d->description !== (string) ($data['description'] ?? '')) {
                $changes['description'] = ['changed' => true];
            }
            if (array_key_exists('starts_at', $data) && $this->iso($d->starts_at) !== $this->iso($data['starts_at'] ?? null)) {
                $changes['starts_at'] = ['from' => $this->iso($d->starts_at), 'to' => $this->iso($data['starts_at'] ?? null)];
            }

            $oldDue = $d->due_at;
            $oldAssignee = $d->assignee;

            $d->fill(array_intersect_key($data, array_flip(['title', 'description', 'project_id', 'starts_at', 'priority'])));

            if ($dueChanged) {
                $d->due_at = $newDue;
                $d->reminder_sent_at = null;
                $d->overdue_notified_at = null;
            }

            if ($assigneeChanged) {
                $self = $newAssignee === $admin->id;
                $d->assigned_to = $newAssignee;
                $d->status = $self ? Deadline::CONFIRMED : Deadline::NEW;
                $d->confirmed_at = $self ? now() : null;
                $d->started_at = null;
                $d->reminder_sent_at = null;
                $d->overdue_notified_at = null;

                // A pending change request belonged to the previous assignee; close it.
                $d->changeRequests()->where('status', 'pending')->update([
                    'status' => 'rejected',
                    'decided_by' => $admin->id,
                    'decided_at' => now(),
                    'decision_comment' => 'Պատասխանատուի փոփոխության պատճառով փակվեց։',
                ]);
            }

            $d->save();
            $d->load('assignee');

            if ($dueChanged) {
                $this->record($d, $admin, 'due_changed', ['from' => $this->iso($oldDue), 'to' => $this->iso($d->due_at)]);
                ActivityLogger::log($admin, 'deadline.changed', $d, "«{$d->title}»՝ ժամկետը փոխվեց", ['from' => $this->iso($oldDue), 'to' => $this->iso($d->due_at)]);
            }
            if ($assigneeChanged) {
                $this->record($d, $admin, 'reassigned', ['from' => $oldAssignee?->name, 'to' => $d->assignee->name]);
                ActivityLogger::log($admin, 'deadline.assigned', $d, "«{$d->title}» → {$d->assignee->name}", ['assigned_to' => $d->assigned_to]);
            }
            if ($changes) {
                $this->record($d, $admin, 'updated', ['fields' => array_keys($changes), 'changes' => $changes]);
                ActivityLogger::log($admin, 'deadline.updated', $d, "Խմբագրվեց՝ {$d->title}", ['fields' => array_keys($changes)]);
            }

            if ($assigneeChanged) {
                Notifier::send($d->assignee, 'deadline.assigned', 'Նոր դեդլայն', "«{$d->title}»՝ ժամկետ {$this->fmt($d)}։", $this->link($d), ['deadline_id' => $d->id], $admin);
                Notifier::send($oldAssignee, 'deadline.reassigned', 'Դեդլայնը ձեզնից հանվեց', "«{$d->title}» դեդլայնի պատասխանատուն փոխվել է։", $this->link($d), ['deadline_id' => $d->id], $admin);
            } elseif ($dueChanged || $changes) {
                $body = $dueChanged
                    ? "«{$d->title}»՝ նոր ժամկետ {$this->fmt($d)} (նախկինը՝ ".Fmt::dt($oldDue).')։'
                    : "«{$d->title}» դեդլայնը խմբագրվել է։";
                Notifier::send($d->assignee, 'deadline.changed', 'Դեդլայնը փոփոխվեց', $body, $this->link($d), ['deadline_id' => $d->id], $admin);
            }

            return $d;
        });
    }

    public function delete(Deadline $deadline, User $admin): void
    {
        DB::transaction(function () use ($deadline, $admin) {
            $d = $this->lock($deadline);
            $d->delete();
            ActivityLogger::log($admin, 'deadline.deleted', $d, "Ջնջվեց դեդլայն՝ {$d->title}");
            Notifier::send($d->assignee, 'deadline.deleted', 'Դեդլայնը չեղարկվեց', "«{$d->title}» դեդլայնը ջնջվել է։", null, [], $admin);
        });
    }

    /* ------------------------------------------------------------- employee side */

    public function confirm(Deadline $deadline, User $user): Deadline
    {
        return DB::transaction(function () use ($deadline, $user) {
            $d = $this->lock($deadline);
            $this->ensure($d, [Deadline::NEW]);

            $d->update(['status' => Deadline::CONFIRMED, 'confirmed_at' => now()]);
            $this->record($d, $user, 'confirmed');
            ActivityLogger::log($user, 'deadline.confirmed', $d, "Հաստատվեց՝ {$d->title}");
            Notifier::send($d->creator, 'deadline.confirmed', 'Դեդլայնը հաստատվեց', "«{$d->title}» — հաստատեց {$user->name}", $this->link($d), ['deadline_id' => $d->id], $user);

            return $d;
        });
    }

    public function start(Deadline $deadline, User $user): Deadline
    {
        return DB::transaction(function () use ($deadline, $user) {
            $d = $this->lock($deadline);
            $this->ensure($d, [Deadline::CONFIRMED]);

            $d->update(['status' => Deadline::IN_PROGRESS, 'started_at' => now()]);
            $this->record($d, $user, 'started');
            ActivityLogger::log($user, 'deadline.started', $d, "Սկսվեց՝ {$d->title}");

            return $d;
        });
    }

    public function requestChange(Deadline $deadline, User $user, array $data): DeadlineChangeRequest
    {
        return DB::transaction(function () use ($deadline, $user, $data) {
            $d = $this->lock($deadline);
            $this->ensure($d, [Deadline::NEW, Deadline::CONFIRMED, Deadline::IN_PROGRESS, Deadline::NEEDS_REVISION]);

            abort_if($d->changeRequests()->where('status', 'pending')->exists(), 409, 'Արդեն կա քննարկվող առաջարկ։');

            $proposed = Carbon::parse($data['proposed_due_at'])->utc();
            if ($proposed->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages(['proposed_due_at' => ['Առաջարկվող ժամկետը պետք է լինի ապագայում։']]);
            }

            $request = $d->changeRequests()->create([
                'requested_by' => $user->id,
                'previous_due_at' => $d->due_at,
                'proposed_due_at' => $proposed,
                'reason' => $data['reason'],
                'comment' => $data['comment'] ?? null,
                'status' => 'pending',
                'status_before' => $d->status,
            ]);

            $d->update(['status' => Deadline::PENDING_CONFIRMATION]);

            $this->record($d, $user, 'change_requested', [
                'request_id' => $request->id,
                'from' => $this->iso($request->previous_due_at),
                'to' => $this->iso($proposed),
                'reason' => $data['reason'],
                'comment' => $data['comment'] ?? null,
            ]);
            ActivityLogger::log($user, 'deadline.change_requested', $d, "«{$d->title}»՝ առաջարկվեց նոր ժամկետ", ['to' => $this->iso($proposed)]);

            Notifier::send(
                Notifier::admins(),
                'deadline.change_requested',
                'Ժամկետի փոփոխության առաջարկ',
                "{$user->name}՝ «{$d->title}», նոր ժամկետ՝ ".Fmt::dt($proposed).'։ Պատճառ՝ '.$data['reason'],
                $this->link($d),
                ['deadline_id' => $d->id],
                $user,
            );

            return $request;
        });
    }

    /** @param  array<int, UploadedFile>  $files */
    public function submit(Deadline $deadline, User $user, ?string $text, array $files): Submission
    {
        $stored = [];

        try {
            return DB::transaction(function () use ($deadline, $user, $text, $files, &$stored) {
                $d = $this->lock($deadline);
                $this->ensure($d, [Deadline::CONFIRMED, Deadline::IN_PROGRESS, Deadline::NEEDS_REVISION]);
                abort_unless($d->assigned_to === $user->id, 403);

                // Submissions are append-only: a re-submission is always a new numbered entry.
                $number = (int) $d->submissions()->max('number') + 1;

                $submission = $d->submissions()->create([
                    'user_id' => $user->id,
                    'number' => $number,
                    'text' => $text,
                    'status' => Submission::PENDING,
                ]);

                foreach ($files as $file) {
                    $info = FileStore::store($file, "submissions/{$d->id}/{$submission->id}");
                    $stored[] = $info['path'];
                    $submission->files()->create($info);
                }

                $d->update(['status' => Deadline::SUBMITTED, 'submitted_at' => now()]);

                $this->record($d, $user, 'submitted', ['submission_id' => $submission->id, 'number' => $number, 'files' => count($files)]);
                ActivityLogger::log($user, 'submission.created', $submission, "Հանձնվեց աշխատանք #{$number}՝ {$d->title}", ['deadline_id' => $d->id]);

                Notifier::send(
                    Notifier::admins(),
                    'submission.created',
                    'Նոր հանձնված աշխատանք',
                    "{$user->name}՝ «{$d->title}» (#{$number})",
                    $this->link($d),
                    ['deadline_id' => $d->id],
                    $user,
                );

                return $submission;
            });
        } catch (\Throwable $e) {
            FileStore::deleteMany($stored);
            throw $e;
        }
    }

    /* ---------------------------------------------------------------- admin side */

    public function decideChange(DeadlineChangeRequest $changeRequest, User $admin, string $decision, ?string $comment): Deadline
    {
        return DB::transaction(function () use ($changeRequest, $admin, $decision, $comment) {
            $d = $this->lock($changeRequest->deadline);
            $cr = DeadlineChangeRequest::query()->lockForUpdate()->findOrFail($changeRequest->id);

            abort_unless($cr->status === 'pending', 409, 'Այս առաջարկն արդեն քննարկված է։');
            $this->ensure($d, [Deadline::PENDING_CONFIRMATION]);

            $accepted = $decision === 'accept';

            if ($accepted) {
                // Agreeing to the employee's own proposal counts as confirmation of the new date.
                $d->update([
                    'due_at' => $cr->proposed_due_at,
                    'status' => $cr->status_before === Deadline::NEW ? Deadline::CONFIRMED : $cr->status_before,
                    'confirmed_at' => $d->confirmed_at ?? now(),
                    'reminder_sent_at' => null,
                    'overdue_notified_at' => null,
                ]);
            } else {
                $d->update(['status' => $cr->status_before]);
            }

            $cr->update([
                'status' => $accepted ? 'accepted' : 'rejected',
                'decided_by' => $admin->id,
                'decided_at' => now(),
                'decision_comment' => $comment,
            ]);

            $type = $accepted ? 'change_accepted' : 'change_rejected';
            $this->record($d, $admin, $type, [
                'request_id' => $cr->id,
                'from' => $this->iso($cr->previous_due_at),
                'to' => $this->iso($cr->proposed_due_at),
                'comment' => $comment,
            ]);
            ActivityLogger::log($admin, "deadline.{$type}", $d, ($accepted ? 'Ընդունվեց' : 'Մերժվեց')." փոփոխության առաջարկը՝ {$d->title}");

            Notifier::send(
                $cr->requestedBy,
                "deadline.{$type}",
                $accepted ? 'Փոփոխությունն ընդունվեց' : 'Փոփոխությունը մերժվեց',
                $accepted
                    ? "«{$d->title}»՝ նոր ժամկետ {$this->fmt($d)}".($comment ? "։ {$comment}" : '')
                    : "«{$d->title}»՝ ժամկետը մնում է {$this->fmt($d)}".($comment ? "։ {$comment}" : ''),
                $this->link($d),
                ['deadline_id' => $d->id],
                $admin,
            );

            return $d;
        });
    }

    public function startReview(Deadline $deadline, User $admin): Deadline
    {
        return DB::transaction(function () use ($deadline, $admin) {
            $d = $this->lock($deadline);
            $this->ensure($d, [Deadline::SUBMITTED]);

            $d->update(['status' => Deadline::UNDER_REVIEW]);
            $this->record($d, $admin, 'review_started');
            ActivityLogger::log($admin, 'submission.review_started', $d, "Սկսվեց ստուգումը՝ {$d->title}");

            return $d;
        });
    }

    public function review(Submission $submission, User $admin, string $decision, ?string $feedback): Deadline
    {
        return DB::transaction(function () use ($submission, $admin, $decision, $feedback) {
            $d = $this->lock($submission->deadline);
            $this->ensure($d, Deadline::REVIEW_STATUSES);

            $s = Submission::query()->lockForUpdate()->findOrFail($submission->id);
            abort_unless($s->status === Submission::PENDING, 409, 'Այս հանձնումն արդեն ստուգված է։');
            abort_unless($s->number === (int) $d->submissions()->max('number'), 409, 'Սա վերջին հանձնումը չէ։');

            $approved = $decision === 'approve';

            $s->update([
                'status' => $approved ? Submission::APPROVED : Submission::NEEDS_REVISION,
                'feedback' => $feedback,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            if ($approved) {
                $d->update(['status' => Deadline::COMPLETED, 'completed_at' => now()]);
                $this->record($d, $admin, 'approved', ['submission_id' => $s->id, 'number' => $s->number, 'feedback' => $feedback]);
                ActivityLogger::log($admin, 'submission.approved', $s, "Հաստատվեց աշխատանքը՝ {$d->title}", ['deadline_id' => $d->id]);
            } else {
                $d->update(['status' => Deadline::NEEDS_REVISION]);
                $this->record($d, $admin, 'revision_requested', ['submission_id' => $s->id, 'number' => $s->number, 'feedback' => $feedback]);
                ActivityLogger::log($admin, 'submission.needs_revision', $s, "Թերի է՝ {$d->title}", ['deadline_id' => $d->id, 'feedback' => $feedback]);
            }

            Notifier::send(
                $d->assignee,
                $approved ? 'submission.approved' : 'submission.needs_revision',
                $approved ? 'Աշխատանքը հաստատվեց' : 'Աշխատանքը թերի է',
                $approved ? "«{$d->title}» — դեդլայնն ավարտված է։" : "«{$d->title}»՝ {$feedback}",
                $this->link($d),
                ['deadline_id' => $d->id],
                $admin,
            );

            return $d;
        });
    }

    /* --------------------------------------------------------------- attachments */

    /** @param  array<int, UploadedFile>  $files */
    public function addAttachments(Deadline $deadline, User $admin, array $files): void
    {
        $stored = [];

        try {
            DB::transaction(function () use ($deadline, $admin, $files, &$stored) {
                foreach ($files as $file) {
                    $attachment = $this->storeAttachment($deadline, $admin, $file);
                    $stored[] = $attachment->path;
                    $this->record($deadline, $admin, 'attachment_added', ['name' => $attachment->original_name]);
                    ActivityLogger::log($admin, 'deadline.attachment_added', $deadline, "Կցվեց ֆայլ՝ {$attachment->original_name}");
                }
            });
        } catch (\Throwable $e) {
            FileStore::deleteMany($stored);
            throw $e;
        }
    }

    public function removeAttachment(DeadlineAttachment $attachment, User $admin): void
    {
        $deadline = $attachment->deadline;
        $path = $attachment->path;
        $name = $attachment->original_name;

        DB::transaction(function () use ($attachment, $deadline, $admin, $name) {
            $attachment->delete();
            $this->record($deadline, $admin, 'attachment_removed', ['name' => $name]);
            ActivityLogger::log($admin, 'deadline.attachment_removed', $deadline, "Հեռացվեց ֆայլ՝ {$name}");
        });

        FileStore::deleteMany([$path]);
    }

    private function storeAttachment(Deadline $deadline, User $user, UploadedFile $file): DeadlineAttachment
    {
        $info = FileStore::store($file, "deadlines/{$deadline->id}");

        return $deadline->attachments()->create($info + ['uploaded_by' => $user->id]);
    }
}
