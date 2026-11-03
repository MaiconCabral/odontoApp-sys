<?php

namespace App\Domains\Clinical\Actions;

use App\Domains\Agenda\Models\Patient;
use App\Domains\Clinical\DTOs\ClinicalNoteData;
use App\Domains\Clinical\Models\ClinicalNote;
use App\Domains\Identity\Models\User;

class AddClinicalNote
{
    /** Appends a note to the medical record. Earlier notes are never changed. */
    public function handle(Patient $patient, ClinicalNoteData $data, User $author): ClinicalNote
    {
        return ClinicalNote::create([
            'patient_id' => $patient->id,
            'appointment_id' => $data->appointmentId,
            'author_id' => $author->id,
            'amends_id' => $data->amendsId,
            'body' => $data->body,
        ]);
    }
}
