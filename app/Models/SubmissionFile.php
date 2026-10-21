<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionFile extends Model
{
    protected $fillable = ['submission_id', 'original_name', 'path', 'mime', 'size', 'kind'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
