<?php

namespace App\Domains\Identity\Support;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use Illuminate\Validation\ValidationException;

// Keeps every clinic with an administrator. Call it inside a transaction, so the lock holds.
class AdminGuard
{
    /** Refuses removing the own access or the last active admin. */
    public function ensureCanLoseAccess(User $target, User $actor): void
    {
        if ($target->is($actor)) {
            throw ValidationException::withMessages([
                'user' => 'Você não pode remover o próprio acesso.',
            ]);
        }

        // Two admins can remove each other at the same moment; the lock serializes them here.
        if ($target->isAdmin() && $target->active && $this->activeAdmins() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'A clínica precisa ter ao menos um administrador ativo.',
            ]);
        }
    }

    /** Counts the active admins, locking their rows. */
    private function activeAdmins(): int
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->where('active', true)
            ->lockForUpdate()
            ->count();
    }
}
