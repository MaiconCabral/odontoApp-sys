<?php

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\DTOs\UserData;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Support\AdminGuard;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    /** Receives the rule that protects administrators. */
    public function __construct(private readonly AdminGuard $guard) {}

    /** Updates a team member, keeping an active admin. */
    public function handle(User $user, UserData $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            if ($this->losesAdministration($user, $data)) {
                $this->guard->ensureCanLoseAccess($user, $actor);
            }

            $user->update([
                'name' => $data->name,
                'email' => $data->email,
                'role' => $data->role,
                'specialty' => $data->specialty,
                'attends_patients' => $data->attends(),
                'active' => $data->active,
            ]);

            return $user;
        });
    }

    /** Tells whether the change deactivates or demotes the user. */
    private function losesAdministration(User $user, UserData $data): bool
    {
        return ! $data->active || ($user->isAdmin() && $data->role !== UserRole::Admin);
    }
}
