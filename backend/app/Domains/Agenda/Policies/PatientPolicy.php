<?php

namespace App\Domains\Agenda\Policies;

use App\Domains\Agenda\Models\Patient;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;

class PatientPolicy
{
    /** Admins and reception keep the patient records. */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Reception], true);
    }

    /** Admins and reception see a patient record. */
    public function view(User $user, Patient $patient): bool
    {
        return $this->viewAny($user);
    }

    /** Admins and reception add patients. */
    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    /** Admins and reception edit patients. */
    public function update(User $user, Patient $patient): bool
    {
        return $this->viewAny($user);
    }

    /** Admins and reception delete patients. */
    public function delete(User $user, Patient $patient): bool
    {
        return $this->viewAny($user);
    }
}
