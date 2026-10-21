<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(
        ?User $user,
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        array $properties = [],
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $user?->getKey(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'description' => $description,
            'properties' => $properties ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
