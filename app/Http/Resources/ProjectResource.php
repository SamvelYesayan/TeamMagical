<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Project */
class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'status' => $this->status,
            'deadlines_count' => $this->whenCounted('deadlines'),
            'active_deadlines_count' => $this->when(isset($this->active_deadlines_count), fn () => (int) $this->active_deadlines_count),
            'created_at' => $this->created_at,
        ];
    }
}
