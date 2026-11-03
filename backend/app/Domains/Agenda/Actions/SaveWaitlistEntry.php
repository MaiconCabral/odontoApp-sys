<?php

namespace App\Domains\Agenda\Actions;

use App\Domains\Agenda\DTOs\WaitlistData;
use App\Domains\Agenda\Enums\WaitlistStatus;
use App\Domains\Agenda\Models\WaitlistEntry;

class SaveWaitlistEntry
{
    /** Adds an entry, or updates it. Moving an entry back to waiting allows a new offer. */
    public function handle(WaitlistData $data, ?WaitlistEntry $entry = null): WaitlistEntry
    {
        $entry ??= new WaitlistEntry(['status' => WaitlistStatus::Waiting]);
        $status = $data->status ?? $entry->status;

        $entry->fill([
            'patient_id' => $data->patientId,
            'procedure_id' => $data->procedureId,
            'professional_id' => $data->professionalId,
            'from_date' => $data->fromDate,
            'to_date' => $data->toDate,
            'period' => $data->period,
            'notes' => $data->notes,
            'status' => $status,
            'notified_at' => $status === WaitlistStatus::Waiting ? null : $entry->notified_at,
        ])->save();

        return $entry;
    }
}
