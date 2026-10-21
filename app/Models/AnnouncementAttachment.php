<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementAttachment extends Model
{
    protected $fillable = ['announcement_id', 'original_name', 'path', 'mime', 'size', 'kind'];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }
}
