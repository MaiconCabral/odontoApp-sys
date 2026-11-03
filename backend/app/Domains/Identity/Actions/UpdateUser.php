<?php

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\DTOs\UserData;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Support\AdminGuard;
use App\Domains\Tenancy\Support\PlanLimits;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    /** Receives the rule that protects administrators and the limits of the plan. */
    public function __construct(
        private readonly AdminGuard $guard,
        private readonly PlanLimits $limits,
    ) {}

    /** Updates a team member, keeping an active admin and the plan limit. */
    public function handle(User $user, UserData $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            if ($this->losesAdministration($user, $data)) {
                $this->guard->ensureCanLoseAccess($user, $actor);
            }

            if ($this->startsCounting($user, $data)) {
                $this->limits->ensureCanAddProfessional($user);
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

    /**
     * Tells whether the user becomes an active professional now.
     * Who already counted keeps the place, even after a downgrade.
     */
    private function startsCounting(User $user, UserData $data): bool
    {
        $countsNow = $user->active && $user->attends_patients;

        return ! $countsNow && $data->active && $data->attends();
    }

    /** Tells whether the change deactivates or demotes the user. */
    private function losesAdministration(User $user, UserData $data): bool
    {
        return ! $data->active || ($user->isAdmin() && $data->role !== UserRole::Admin);
    }
}
