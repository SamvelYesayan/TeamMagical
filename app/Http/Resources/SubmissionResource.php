<?php

namespace App\Http\Resources;

use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Submission */
class SubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'user' => Fmt::person($this->user),
            'text' => $this->text,
            'status' => $this->status,
            'feedback' => $this->feedback,
            'reviewed_by' => Fmt::person($this->reviewer),
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
            'files' => FileResource::collection($this->whenLoaded('files')),
        ];
    }
}
