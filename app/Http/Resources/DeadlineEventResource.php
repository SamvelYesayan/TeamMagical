<?php

namespace App\Http\Resources;

use App\Support\Fmt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\DeadlineEvent */
class DeadlineEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'user' => Fmt::person($this->user),
            'data' => $this->data,
            'created_at' => $this->created_at,
        ];
    }
}
