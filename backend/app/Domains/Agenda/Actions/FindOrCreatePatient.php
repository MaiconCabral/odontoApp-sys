<?php

namespace App\Domains\Agenda\Actions;

use App\Domains\Agenda\DTOs\PatientData;
use App\Domains\Agenda\Models\Patient;
use Illuminate\Support\Str;

class FindOrCreatePatient
{
    /** Finds the patient by phone or e-mail, or creates a new record. */
    public function handle(PatientData $data): Patient
    {
        $digits = Patient::digits($data->phone);
        $email = $data->email ? Str::lower(trim($data->email)) : null;

        $existing = Patient::query()
            ->where(function ($query) use ($digits, $email) {
                $query->where('phone_digits', $digits);

                if ($email) {
                    $query->orWhere('email', $email);
                }
            })
            ->orderBy('id')
            ->first();

        return $existing ?? Patient::create($data->toAttributes());
    }
}
