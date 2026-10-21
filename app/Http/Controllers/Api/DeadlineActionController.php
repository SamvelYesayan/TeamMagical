<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeadlineResource;
use App\Models\Deadline;
use App\Models\DeadlineAttachment;
use App\Models\DeadlineChangeRequest;
use App\Models\Submission;
use App\Rules\WorkFile;
use App\Services\DeadlineWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Workflow transitions. Each action is authorised by DeadlinePolicy and re-validated inside the workflow. */
class DeadlineActionController extends Controller
{
    public function __construct(private readonly DeadlineWorkflow $workflow)
    {
    }

    private function respond(Request $request, Deadline $deadline, string $message): JsonResponse
    {
        $deadline->refresh()->load(['project', 'assignee', 'creator', 'pendingChangeRequest'])->loadCount(['attachments', 'submissions']);

        return response()->json(['message' => $message, 'data' => (new DeadlineResource($deadline))->resolve($request)]);
    }

    public function confirm(Request $request, Deadline $deadline): JsonResponse
    {
        $this->authorize('confirm', $deadline);

        return $this->respond($request, $this->workflow->confirm($deadline, $request->user()), 'Դեդլայնը հաստատվեց։');
    }

    public function start(Request $request, Deadline $deadline): JsonResponse
    {
        $this->authorize('start', $deadline);

        return $this->respond($request, $this->workflow->start($deadline, $request->user()), 'Աշխատանքը սկսվեց։');
    }

    public function requestChange(Request $request, Deadline $deadline): JsonResponse
    {
        $this->authorize('requestChange', $deadline);

        $data = $request->validate([
            'proposed_due_at' => ['required', 'date', 'after:now'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->workflow->requestChange($deadline, $request->user(), $data);

        return $this->respond($request, $deadline, 'Առաջարկը ուղարկվեց ադմինիստրատորին։');
    }

    public function decideChange(Request $request, DeadlineChangeRequest $changeRequest): JsonResponse
    {
        $deadline = $changeRequest->deadline;
        $this->authorize('decideChange', $deadline);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['accept', 'reject'])],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->workflow->decideChange($changeRequest, $request->user(), $data['decision'], $data['comment'] ?? null);

        return $this->respond($request, $deadline, $data['decision'] === 'accept' ? 'Առաջարկն ընդունվեց։' : 'Առաջարկը մերժվեց։');
    }

    public function submit(Request $request, Deadline $deadline): JsonResponse
    {
        $this->authorize('submit', $deadline);

        $data = $request->validate([
            'text' => ['nullable', 'string', 'max:20000'],
            'files' => ['nullable', 'array', 'max:'.config('magical.uploads.max_files')],
            'files.*' => [new WorkFile],
        ]);

        $files = $request->file('files', []);
        $text = isset($data['text']) ? trim($data['text']) : '';

        if ($text === '' && count($files) === 0) {
            throw ValidationException::withMessages(['text' => ['Գրեք բացատրություն կամ կցեք առնվազն մեկ ֆայլ։']]);
        }

        $this->workflow->submit($deadline, $request->user(), $text !== '' ? $text : null, $files);

        return $this->respond($request, $deadline, 'Աշխատանքը հանձնվեց։');
    }

    public function startReview(Request $request, Deadline $deadline): JsonResponse
    {
        $this->authorize('startReview', $deadline);

        return $this->respond($request, $this->workflow->startReview($deadline, $request->user()), 'Ստուգումը սկսվեց։');
    }

    public function review(Request $request, Submission $submission): JsonResponse
    {
        $deadline = $submission->deadline;
        $this->authorize('review', $deadline);

        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'needs_revision'])],
            'feedback' => [Rule::requiredIf($request->input('decision') === 'needs_revision'), 'nullable', 'string', 'min:3', 'max:5000'],
        ]);

        $feedback = isset($data['feedback']) ? trim($data['feedback']) : null;
        if ($data['decision'] === 'needs_revision' && ($feedback === null || $feedback === '')) {
            throw ValidationException::withMessages(['feedback' => ['Նշեք, թե ինչու է աշխատանքը թերի։']]);
        }

        $this->workflow->review($submission, $request->user(), $data['decision'], $feedback ?: null);

        return $this->respond(
            $request,
            $deadline,
            $data['decision'] === 'approve' ? 'Աշխատանքը հաստատվեց, դեդլայնն ավարտված է։' : 'Աշխատանքը վերադարձվեց ուղղման։'
        );
    }

    public function addAttachments(Request $request, Deadline $deadline): JsonResponse
    {
        $this->authorize('manageAttachments', $deadline);

        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:'.config('magical.uploads.max_files')],
            'files.*' => [new WorkFile],
        ]);

        $this->workflow->addAttachments($deadline, $request->user(), $request->file('files'));

        return $this->respond($request, $deadline, 'Ֆայլերը կցվեցին։');
    }

    public function removeAttachment(Request $request, DeadlineAttachment $attachment): JsonResponse
    {
        $deadline = $attachment->deadline;
        $this->authorize('manageAttachments', $deadline);

        $this->workflow->removeAttachment($attachment, $request->user());

        return $this->respond($request, $deadline, 'Ֆայլը հեռացվեց։');
    }
}
