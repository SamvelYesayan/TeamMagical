<?php

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;

class Fmt
{
    /** Date/time for human readable notification texts, in the company time zone. */
    public static function dt(?CarbonInterface $date): string
    {
        return $date ? $date->copy()->setTimezone('Asia/Yerevan')->format('d.m.Y H:i') : '—';
    }

    public static function person(?User $user): ?array
    {
        return $user ? ['id' => $user->id, 'name' => $user->name, 'position' => $user->position] : null;
    }
}
