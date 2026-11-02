<?php

namespace App\Domains\Agenda\Policies;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;

class AppointmentPolicy
{
    /** Admins and reception see every professional agenda. */
    public function viewAll(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Reception], true);
    }
}
