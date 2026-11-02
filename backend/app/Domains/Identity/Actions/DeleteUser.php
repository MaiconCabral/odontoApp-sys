<?php

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Support\AdminGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteUser
{
    /** Receives the rule that protects administrators. */
    public function __construct(private readonly AdminGuard $guard) {}

    /** Deletes a team member without appointments. */
    public function handle(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            $this->guard->ensureCanLoseAccess($user, $actor);

            if ($user->appointments()->exists()) {
                throw ValidationException::withMessages([
                    'user' => 'Este usuário tem agendamentos. Desative a conta em vez de excluí-la.',
                ]);
            }

            $user->delete();
        });
    }
}
