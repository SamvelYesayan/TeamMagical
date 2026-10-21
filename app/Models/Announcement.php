<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';

    protected $fillable = [
        'title', 'content', 'image_path', 'image_mime', 'author_id', 'status', 'published_at', 'notified_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'notified_at' => 'datetime'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id')->withTrashed();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AnnouncementAttachment::class)->orderBy('id');
    }

    /** Published and its publication date has been reached. */
    public function isVisible(): bool
    {
        return $this->status === self::PUBLISHED && $this->published_at !== null && $this->published_at->lessThanOrEqualTo(now());
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', self::PUBLISHED)->where('published_at', '<=', now());
    }
}
