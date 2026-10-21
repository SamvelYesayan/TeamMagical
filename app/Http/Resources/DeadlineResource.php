<?php

namespace App\Http\Resources;

use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Deadline */
class DeadlineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $can = fn (string $ability): bool => $user ? $user->can($ability, $this->resource) : false;
        $canViewSubmissions = $can('viewSubmissions');

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'project' => $this->project ? ['id' => $this->project->id, 'name' => $this->project->name, 'color' => $this->project->color] : null,
            'assignee' => Fmt::person($this->assignee),
            'creator' => Fmt::person($this->creator),
            'starts_at' => $this->starts_at,
            'due_at' => $this->due_at,
            'priority' => $this->priority,
            'status' => $this->status,
            // "overdue" is computed from the server clock at response time.
            'display_status' => $this->displayStatus(),
            'is_overdue' => $this->isOverdue(),
            'confirmed_at' => $this->confirmed_at,
            'started_at' => $this->started_at,
            'submitted_at' => $this->submitted_at,
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
            'attachments_count' => $this->whenCounted('attachments'),
            'submissions_count' => $this->when($canViewSubmissions && isset($this->submissions_count), fn () => (int) $this->submissions_count),
            'pending_change_request' => $this->whenLoaded('pendingChangeRequest', fn () => $this->pendingChangeRequest ? new ChangeRequestResource($this->pendingChangeRequest) : null),
            'can' => [
                'update' => $can('update'),
                'delete' => $can('delete'),
                'confirm' => $can('confirm'),
                'request_change' => $can('requestChange'),
                'start' => $can('start'),
                'submit' => $can('submit'),
                'start_review' => $can('startReview'),
                'review' => $can('review'),
                'decide_change' => $can('decideChange'),
                'manage_attachments' => $can('manageAttachments'),
                'view_submissions' => $canViewSubmissions,
            ],
            'attachments' => FileResource::collection($this->whenLoaded('attachments')),
            'change_requests' => ChangeRequestResource::collection($this->whenLoaded('changeRequests')),
            'events' => DeadlineEventResource::collection($this->whenLoaded('events')),
            'submissions' => $this->when($canViewSubmissions && $this->relationLoaded('submissions'), fn () => SubmissionResource::collection($this->submissions)),
        ];
    }
}
