<?php

namespace App\Domains\Tenancy\Policies;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Tenant;

class ClinicPolicy
{
    /** Only admins see the clinic data. */
    public function view(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }

    /** Only admins change the clinic data. */
    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isAdmin();
    }
}
