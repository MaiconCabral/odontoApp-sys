<?php

namespace App\Domains\Agenda\Actions;

use App\Domains\Agenda\DTOs\AppointmentData;
use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Support\AvailabilityChecker;
use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Enums\PatientMessage;
use App\Domains\Notifications\PatientNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RescheduleAppointment
{
    /** Receives the booking rules. */
    public function __construct(
        private readonly AvailabilityChecker $availability,
        private readonly PatientNotifier $notifier,
    ) {}

    /** Moves or edits a booked appointment. A confirmed one goes back to scheduled. */
    public function handle(Appointment $appointment, AppointmentData $data, User $author): Appointment
    {
        if (! in_array($appointment->status, [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed], true)) {
            throw ValidationException::withMessages(['status' => 'Só é possível remarcar consultas agendadas ou confirmadas.']);
        }

        return DB::transaction(function () use ($appointment, $data, $author) {
            $this->availability->lock($data->professionalId, $data->roomId);
            $this->availability->ensure($data->startsAt, $data->procedure->minutes(), $data->professionalId, $data->roomId, $appointment->id);

            $previousStart = $appointment->starts_at->copy();

            $appointment->fill([
                'patient_id' => $data->patientId,
                'professional_id' => $data->professionalId,
                'room_id' => $data->roomId,
                'procedure_id' => $data->procedure->id,
                // The price of the day it was booked is kept, unless the procedure changes.
                'price_cents' => $appointment->procedure_id === $data->procedure->id ? $appointment->price_cents : $data->procedure->price_cents,
                'starts_at' => $data->startsAt,
                'ends_at' => $data->endsAt(),
                'notes' => $data->notes,
            ]);

            if ($appointment->isDirty('starts_at')) {
                $reason = 'Remarcada de '.$previousStart->format('d/m/Y H:i').' para '.$data->startsAt->format('d/m/Y H:i').'.';
                // A new time deserves a new reminder.
                $appointment->reminder_sent_at = null;
                $appointment->moveTo(AppointmentStatus::Scheduled, $author, $reason);
                $this->notifier->send($appointment, PatientMessage::Rescheduled, ['previous' => PatientNotifier::when($previousStart)]);
            } else {
                $appointment->save();
            }

            return $appointment;
        });
    }
}
