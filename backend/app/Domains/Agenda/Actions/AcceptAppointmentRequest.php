<?php

namespace App\Domains\Agenda\Actions;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Support\AvailabilityChecker;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcceptAppointmentRequest
{
    /** Receives the booking rules. */
    public function __construct(private readonly AvailabilityChecker $availability) {}

    /** Turns a patient request into a scheduled appointment in a free room. */
    public function handle(Appointment $appointment, int $roomId, User $author): Appointment
    {
        if ($appointment->status !== AppointmentStatus::Requested) {
            throw ValidationException::withMessages(['status' => 'Este pedido já foi respondido.']);
        }

        return DB::transaction(function () use ($appointment, $roomId, $author) {
            $this->availability->lock($appointment->professional_id, $roomId);
            $this->availability->ensure(
                CarbonImmutable::instance($appointment->starts_at),
                $appointment->procedure->minutes(),
                $appointment->professional_id,
                $roomId,
                $appointment->id,
            );

            $appointment->room_id = $roomId;
            $appointment->moveTo(AppointmentStatus::Scheduled, $author, 'Pedido aceito.');

            return $appointment;
        });
    }
}
