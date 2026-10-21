<?php

namespace App\Policies;

use App\Models\Deadline;
use App\Models\User;

class DeadlinePolicy
{
    private function assignee(User $user, Deadline $deadline): bool
    {
        return $deadline->assigned_to === $user->id;
    }

    /** Every employee may see the team's deadlines (title, owner, due date, status). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Deadline $deadline): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Deadline $deadline): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Deadline $deadline): bool
    {
        return $user->isAdmin();
    }

    public function manageAttachments(User $user, Deadline $deadline): bool
    {
        return $user->isAdmin();
    }

    public function confirm(User $user, Deadline $deadline): bool
    {
        return $this->assignee($user, $deadline) && $deadline->status === Deadline::NEW;
    }

    /** Self-assigned deadlines need no negotiation: the admin simply edits the date. */
    public function requestChange(User $user, Deadline $deadline): bool
    {
        return $this->assignee($user, $deadline)
            && $deadline->created_by !== $deadline->assigned_to
            && in_array($deadline->status, [Deadline::NEW, Deadline::CONFIRMED, Deadline::IN_PROGRESS, Deadline::NEEDS_REVISION], true);
    }

    public function start(User $user, Deadline $deadline): bool
    {
        return $this->assignee($user, $deadline) && $deadline->status === Deadline::CONFIRMED;
    }

    public function submit(User $user, Deadline $deadline): bool
    {
        return $this->assignee($user, $deadline)
            && in_array($deadline->status, [Deadline::CONFIRMED, Deadline::IN_PROGRESS, Deadline::NEEDS_REVISION], true);
    }

    public function startReview(User $user, Deadline $deadline): bool
    {
        return $user->isAdmin() && $deadline->status === Deadline::SUBMITTED;
    }

    public function review(User $user, Deadline $deadline): bool
    {
        return $user->isAdmin() && in_array($deadline->status, Deadline::REVIEW_STATUSES, true);
    }

    /** A pending change request exists exactly while the deadline is in "pending_confirmation". */
    public function decideChange(User $user, Deadline $deadline): bool
    {
        return $user->isAdmin() && $deadline->status === Deadline::PENDING_CONFIRMATION;
    }

    /** Submitted work (text + files) is visible only to the assignee and administrators. */
    public function viewSubmissions(User $user, Deadline $deadline): bool
    {
        return $user->isAdmin() || $this->assignee($user, $deadline);
    }
}
