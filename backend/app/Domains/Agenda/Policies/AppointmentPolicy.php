<?php

namespace App\Domains\Agenda\Policies;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;

class AppointmentPolicy
{
    /** Admins and reception see every professional agenda. */
    public function viewAll(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Reception], true);
    }

    /** Admins and reception book, move and answer requests. */
    public function manage(User $user): bool
    {
        return $this->viewAll($user);
    }

    /** Professionals see only their own appointments. */
    public function view(User $user, Appointment $appointment): bool
    {
        return $this->viewAll($user) || $appointment->professional_id === $user->id;
    }

    /** Professionals only mark their own appointments as completed or missed. */
    public function changeStatus(User $user, Appointment $appointment, AppointmentStatus $next): bool
    {
        if ($this->manage($user)) {
            return true;
        }

        return $appointment->professional_id === $user->id
            && in_array($next, [AppointmentStatus::Completed, AppointmentStatus::NoShow], true);
    }
}
