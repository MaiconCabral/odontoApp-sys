<?php

namespace App\Domains\Agenda\Policies;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;

class SchedulePolicy
{
    /** Only admins change the opening hours. */
    public function updateBusinessHours(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Admins and reception register holidays and days off. */
    public function manageBlocks(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Reception], true);
    }
}
