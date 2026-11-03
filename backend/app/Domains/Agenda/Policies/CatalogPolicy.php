<?php

namespace App\Domains\Agenda\Policies;

use App\Domains\Identity\Models\User;

// Procedures and rooms: everyone signed in reads them, only admins change them.
class CatalogPolicy
{
    /** Every member of the clinic sees the list. */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Only admins add items. */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Only admins edit items. */
    public function update(User $user, mixed $item): bool
    {
        return $user->isAdmin();
    }

    /** Only admins delete items. */
    public function delete(User $user, mixed $item): bool
    {
        return $user->isAdmin();
    }
}
