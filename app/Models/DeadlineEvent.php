<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeadlineEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['deadline_id', 'user_id', 'type', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array', 'created_at' => 'datetime'];
    }

    public function deadline(): BelongsTo
    {
        return $this->belongsTo(Deadline::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
