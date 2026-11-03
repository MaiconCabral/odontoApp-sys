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

    /** Admins and reception see any patient; a professional sees the patients they attend. */
    public function view(User $user, Patient $patient): bool
    {
        return $this->viewAny($user) || $this->attends($user, $patient);
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

    /**
     * Medical record, odontogram and attachments are health data (LGPD).
     * Admins see them; a professional sees those of their own patients; reception never does.
     */
    public function viewRecord(User $user, Patient $patient): bool
    {
        return $user->role === UserRole::Admin || $this->attends($user, $patient);
    }

    /** Who sees the record may also write in it. */
    public function writeRecord(User $user, Patient $patient): bool
    {
        return $this->viewRecord($user, $patient);
    }

    /** Only admins remove a file of the record. */
    public function deleteAttachment(User $user, Patient $patient): bool
    {
        return $user->role === UserRole::Admin;
    }

    /** Whether the user is a professional with an appointment of this patient. */
    private function attends(User $user, Patient $patient): bool
    {
        return $user->attends_patients
            && $patient->appointments()->where('professional_id', $user->id)->exists();
    }
}
