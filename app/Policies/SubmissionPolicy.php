<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function view(User $user, Submission $submission): bool
    {
        return $user->isAdmin()
            || $submission->user_id === $user->id
            || $submission->deadline?->assigned_to === $user->id;
    }
}
