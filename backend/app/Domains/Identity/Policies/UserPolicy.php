<?php

namespace App\Domains\Identity\Policies;

use App\Domains\Identity\Models\User;

class UserPolicy
{
    /** Only admins list the team. */
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    /** Only admins see a team member. */
    public function view(User $actor, User $user): bool
    {
        return $actor->isAdmin();
    }

    /** Only admins add team members. */
    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    /** Only admins edit team members. */
    public function update(User $actor, User $user): bool
    {
        return $actor->isAdmin();
    }

    /** Only admins delete team members. */
    public function delete(User $actor, User $user): bool
    {
        return $actor->isAdmin();
    }
}
