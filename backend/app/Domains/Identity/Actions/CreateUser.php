<?php

namespace App\Domains\Identity\Actions;

use App\Domains\Identity\DTOs\UserData;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Notifications\AccountPasswordLink;
use App\Domains\Tenancy\Support\PlanLimits;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CreateUser
{
    private const TONES = ['cyan', 'amber', 'green'];

    /** Receives the limits of the plan. */
    public function __construct(private readonly PlanLimits $limits) {}

    /**
     * Creates a team member and e-mails the link to set the password.
     * Admins never choose passwords for others: the new user sets one through the e-mailed link.
     */
    public function handle(UserData $data): User
    {
        $user = DB::transaction(function () use ($data) {
            if ($data->active && $data->attends()) {
                $this->limits->ensureCanAddProfessional();
            }

            return User::create([
                'name' => $data->name,
                'email' => $data->email,
                'password' => Str::password(40),
                'role' => $data->role,
                'specialty' => $data->specialty,
                'attends_patients' => $data->attends(),
                'tone' => self::TONES[User::query()->count() % count(self::TONES)],
                'active' => $data->active,
            ]);
        });

        $token = Password::broker()->createToken($user);
        $user->notify(new AccountPasswordLink(AccountPasswordLink::urlFor($user, $token), isInvitation: true));

        return $user;
    }
}
