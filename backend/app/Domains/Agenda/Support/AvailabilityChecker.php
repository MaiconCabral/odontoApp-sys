<?php

namespace App\Domains\Agenda\Support;

use App\Domains\Agenda\Models\Room;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

// Single entry point for booking rules. Call it inside the transaction that saves the appointment.
class AvailabilityChecker
{
    /**
     * Locks the professional and the room rows, so concurrent bookings of them wait in line.
     * The lock lasts until the surrounding transaction ends.
     */
    public function lock(?int $professionalId, ?int $roomId): void
    {
        if ($professionalId) {
            User::query()->whereKey($professionalId)->lockForUpdate()->first();
        }

        if ($roomId) {
            Room::query()->whereKey($roomId)->lockForUpdate()->first();
        }
    }

    /** Refuses a past, closed, blocked or taken time with field errors. */
    public function ensure(CarbonImmutable $start, int $minutes, ?int $professionalId, ?int $roomId, ?int $ignoreAppointmentId = null): void
    {
        if ($start <= now()) {
            throw ValidationException::withMessages(['starts_at' => 'Escolha um horário futuro.']);
        }

        $problems = DaySchedule::load($start, $ignoreAppointmentId)->problems($start, $minutes, $professionalId, $roomId);

        if ($problems) {
            throw ValidationException::withMessages($problems);
        }
    }
}
