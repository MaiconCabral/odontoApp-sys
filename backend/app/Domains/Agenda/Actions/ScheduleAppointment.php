<?php

namespace App\Domains\Agenda\Actions;

use App\Domains\Agenda\DTOs\AppointmentData;
use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Support\AvailabilityChecker;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\DB;

class ScheduleAppointment
{
    /** Receives the booking rules. */
    public function __construct(private readonly AvailabilityChecker $availability) {}

    /** Books an appointment from the panel, refusing taken or closed times. */
    public function handle(AppointmentData $data, User $author): Appointment
    {
        return DB::transaction(function () use ($data, $author) {
            $this->availability->lock($data->professionalId, $data->roomId);
            $this->availability->ensure($data->startsAt, $data->procedure->minutes(), $data->professionalId, $data->roomId);

            $appointment = new Appointment([
                'patient_id' => $data->patientId,
                'professional_id' => $data->professionalId,
                'room_id' => $data->roomId,
                'procedure' => $data->procedure,
                'starts_at' => $data->startsAt,
                'ends_at' => $data->endsAt(),
                'source' => Appointment::SOURCE_INTERNAL,
                'notes' => $data->notes,
            ]);
            $appointment->moveTo(AppointmentStatus::Scheduled, $author);

            return $appointment;
        });
    }
}
