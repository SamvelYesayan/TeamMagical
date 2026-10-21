<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deadline extends Model
{
    use SoftDeletes;

    public const NEW = 'new';
    public const PENDING_CONFIRMATION = 'pending_confirmation';
    public const CONFIRMED = 'confirmed';
    public const IN_PROGRESS = 'in_progress';
    public const SUBMITTED = 'submitted';
    public const UNDER_REVIEW = 'under_review';
    public const COMPLETED = 'completed';
    public const NEEDS_REVISION = 'needs_revision';

    /** Statuses in which the employee still owes work (and the clock is running). */
    public const ACTIVE_STATUSES = [
        self::NEW, self::PENDING_CONFIRMATION, self::CONFIRMED, self::IN_PROGRESS, self::NEEDS_REVISION,
    ];

    public const REVIEW_STATUSES = [self::SUBMITTED, self::UNDER_REVIEW];

    public const PRIORITIES = ['low', 'normal', 'high', 'urgent'];

    protected $fillable = [
        'title', 'description', 'project_id', 'assigned_to', 'created_by', 'starts_at', 'due_at',
        'priority', 'status', 'confirmed_at', 'started_at', 'submitted_at', 'completed_at',
        'reminder_sent_at', 'overdue_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'due_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'overdue_notified_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(DeadlineAttachment::class)->orderBy('id');
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(DeadlineChangeRequest::class)->orderByDesc('id');
    }

    public function pendingChangeRequest(): HasOne
    {
        return $this->hasOne(DeadlineChangeRequest::class)->where('status', 'pending');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeadlineEvent::class)->orderBy('id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class)->orderBy('number');
    }

    /** "Overdue" is derived from the server clock, so it is correct even before the scheduler runs. */
    public function isOverdue(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true) && $this->due_at->isPast();
    }

    public function displayStatus(): string
    {
        return $this->isOverdue() ? 'overdue' : $this->status;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES)->where('due_at', '<', now());
    }
}
