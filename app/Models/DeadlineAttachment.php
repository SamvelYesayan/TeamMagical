<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadlineAttachment extends Model
{
    protected $fillable = ['deadline_id', 'uploaded_by', 'original_name', 'path', 'mime', 'size', 'kind'];

    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class);
    }
}
