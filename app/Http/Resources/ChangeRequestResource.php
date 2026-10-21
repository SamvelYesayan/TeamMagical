<?php

namespace App\Http\Resources;

use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DeadlineChangeRequest */
class ChangeRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'deadline_id' => $this->deadline_id,
            'deadline' => $this->whenLoaded('deadline', fn () => ['id' => $this->deadline->id, 'title' => $this->deadline->title]),
            'requested_by' => Fmt::person($this->requestedBy),
            'previous_due_at' => $this->previous_due_at,
            'proposed_due_at' => $this->proposed_due_at,
            'reason' => $this->reason,
            'comment' => $this->comment,
            'status' => $this->status,
            'decided_by' => Fmt::person($this->decidedBy),
            'decision_comment' => $this->decision_comment,
            'decided_at' => $this->decided_at,
            'created_at' => $this->created_at,
        ];
    }
}
