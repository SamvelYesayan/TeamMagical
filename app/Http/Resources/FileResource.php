<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Works for deadline attachments, submission files and announcement attachments. Never exposes the storage path. */
class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->original_name,
            'size' => (int) $this->size,
            'mime' => $this->mime,
            'kind' => $this->kind,
        ];
    }
}
