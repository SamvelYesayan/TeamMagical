<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const NEEDS_REVISION = 'needs_revision';

    protected $fillable = [
        'deadline_id', 'user_id', 'number', 'text', 'status', 'feedback', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }

    public function files(): HasMany
    {
        return $this->hasMany(SubmissionFile::class)->orderBy('id');
    }
}
