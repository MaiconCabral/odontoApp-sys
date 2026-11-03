<?php

namespace App\Domains\Notifications\Support;

use App\Domains\Agenda\Models\Patient;

// Where each patient can be reached. WhatsApp enters here, with a channel class and toWhatsApp().
class PatientChannels
{
    /**
     * Channels available for the patient, in order of preference.
     *
     * @return list<string>
     */
    public function for(Patient $patient): array
    {
        return $patient->email ? ['mail'] : [];
    }
}
