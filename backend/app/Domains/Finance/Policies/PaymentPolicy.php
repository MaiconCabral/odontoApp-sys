<?php

namespace App\Domains\Finance\Policies;

use App\Domains\Finance\Models\Payment;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;

class PaymentPolicy
{
    /** Admins and reception see and record payments. */
    public function manage(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Reception], true);
    }

    /** Only admins void a payment, since it changes what the clinic received. */
    public function void(User $user, Payment $payment): bool
    {
        return $user->role === UserRole::Admin;
    }

    /** Reports with money are for admins. */
    public function viewReports(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
