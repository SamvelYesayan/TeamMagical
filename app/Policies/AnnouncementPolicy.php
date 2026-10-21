<?php

namespace App\Policies;

use App\Models\Announcement;
use App\Models\User;

class AnnouncementPolicy
{
    /** Employees see published announcements only; administrators also see drafts and scheduled ones. */
    public function view(User $user, Announcement $announcement): bool
    {
        return $user->isAdmin() || $announcement->isVisible();
    }

    public function manage(User $user): bool
    {
        return $user->isAdmin();
    }
}
