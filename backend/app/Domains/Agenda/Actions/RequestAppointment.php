<?php

namespace App\Domains\Agenda\Actions;

use App\Domains\Agenda\DTOs\BookingRequestData;
use App\Domains\Agenda\DTOs\PatientData;
use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Queries\AvailableSlots;
use App\Domains\Agenda\Support\AvailabilityChecker;
use App\Domains\Notifications\Enums\PatientMessage;
use App\Domains\Notifications\Enums\StaffMessage;
use App\Domains\Notifications\PatientNotifier;
use App\Domains\Notifications\StaffNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestAppointment
{
    /** Receives the booking rules and the patient lookup. */
    public function __construct(
        private readonly AvailabilityChecker $availability,
        private readonly FindOrCreatePatient $patients,
        private readonly AvailableSlots $slots,
        private readonly PatientNotifier $notifier,
        private readonly StaffNotifier $staff,
    ) {}

    /** Saves a public request that holds the time until the clinic answers. */
    public function handle(BookingRequestData $data): Appointment
    {
        $candidates = $data->professionalId ? [$data->professionalId] : $this->slots->attendingIds();

        // Candidates are tried in id order, so concurrent requests lock rows in the same order.
        foreach ($candidates as $professionalId) {
            try {
                return $this->bookWith($data, $professionalId);
            } catch (ValidationException $exception) {
                // A closed clinic or a chosen professional has no other option to try.
                if ($data->professionalId || isset($exception->errors()['starts_at'])) {
                    throw $exception;
                }
            }
        }

        throw ValidationException::withMessages(['starts_at' => 'Este horário acabou de ser reservado. Escolha outro.']);
    }

    /** Books the request with one professional, or fails with field errors. */
    private function bookWith(BookingRequestData $data, int $professionalId): Appointment
    {
        return DB::transaction(function () use ($data, $professionalId) {
            $this->availability->lock($professionalId, null);
            $this->availability->ensure($data->startsAt, $data->procedure->minutes(), $professionalId, null);

            $patient = $this->patients->handle(new PatientData(
                name: $data->name,
                phone: $data->phone,
                email: $data->email,
            ));

            $appointment = new Appointment([
                'patient_id' => $patient->id,
                'professional_id' => $professionalId,
                'procedure_id' => $data->procedure->id,
                'price_cents' => $data->procedure->price_cents,
                'starts_at' => $data->startsAt,
                'ends_at' => $data->startsAt->addMinutes($data->procedure->minutes()),
                'source' => Appointment::SOURCE_PUBLIC,
                'notes' => $data->notes,
            ]);
            $appointment->moveTo(AppointmentStatus::Requested, null);
            $this->notifier->send($appointment, PatientMessage::Requested);
            $this->staff->send($appointment, StaffMessage::BookingRequested);

            return $appointment;
        });
    }
}
