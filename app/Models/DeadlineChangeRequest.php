<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadlineChangeRequest extends Model
{
    protected $fillable = [
        'deadline_id', 'requested_by', 'previous_due_at', 'proposed_due_at', 'reason', 'comment',
        'status', 'status_before', 'decided_by', 'decision_comment', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'previous_due_at' => 'datetime',
            'proposed_due_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class)->withTrashed();
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by')->withTrashed();
    }
}
