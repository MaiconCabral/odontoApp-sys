<?php

namespace App\Domains\Agenda\DTOs;

use App\Domains\Agenda\Models\Procedure;
use Carbon\CarbonImmutable;

readonly class AppointmentData
{
    /** Data to book or move an appointment from the panel. */
    public function __construct(
        public int $patientId,
        public int $professionalId,
        public int $roomId,
        public Procedure $procedure,
        public CarbonImmutable $startsAt,
        public ?string $notes = null,
    ) {}

    /** End of the appointment, from the procedure length. */
    public function endsAt(): CarbonImmutable
    {
        return $this->startsAt->addMinutes($this->procedure->minutes());
    }
}
