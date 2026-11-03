<?php

namespace App\Domains\Clinical\Actions;

use App\Domains\Agenda\Models\Patient;
use App\Domains\Clinical\DTOs\ToothRecordData;
use App\Domains\Clinical\Models\ToothRecord;
use App\Domains\Identity\Models\User;

class RecordToothCondition
{
    /** Adds one change to the odontogram history. Conditions of the whole tooth have no surface. */
    public function handle(Patient $patient, ToothRecordData $data, User $author): ToothRecord
    {
        return ToothRecord::create([
            'patient_id' => $patient->id,
            'author_id' => $author->id,
            'tooth' => $data->tooth,
            'surface' => $data->condition->wholeTooth() ? null : $data->surface,
            'condition' => $data->condition,
            'notes' => $data->notes,
        ]);
    }
}
