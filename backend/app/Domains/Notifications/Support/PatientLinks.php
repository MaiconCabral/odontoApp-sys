<?php

namespace App\Domains\Notifications\Support;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Tenancy\Support\ClinicUrl;

// Links that let a patient confirm or cancel without an account.
// The token signs the clinic, the appointment and its start, so it stops working when the
// appointment moves or starts, and nothing secret is stored in the database.
class PatientLinks
{
    /** Page on the clinic site where the patient answers. */
    public function url(Appointment $appointment): string
    {
        return ClinicUrl::to(tenant(), '/consulta', ['token' => $this->token($appointment)]);
    }

    /** Token of an appointment as it is now. */
    public function token(Appointment $appointment): string
    {
        return $appointment->id.'-'.$this->signature($appointment);
    }

    /** Appointment of a valid, unexpired token, or null. */
    public function resolve(string $token): ?Appointment
    {
        if (! preg_match('/^(\d+)-([a-f0-9]{64})$/', $token, $parts)) {
            return null;
        }

        $appointment = Appointment::query()->with(['procedure', 'professional'])->find((int) $parts[1]);

        if (! $appointment || ! hash_equals($this->signature($appointment), $parts[2]) || $appointment->starts_at <= now()) {
            return null;
        }

        return $appointment;
    }

    /** HMAC of the clinic, the appointment and its start, with the application key. */
    private function signature(Appointment $appointment): string
    {
        $payload = implode('|', [tenant()->getTenantKey(), $appointment->id, $appointment->starts_at->toIso8601String()]);

        return hash_hmac('sha256', $payload, (string) config('app.key'));
    }
}
