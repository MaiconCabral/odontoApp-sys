<?php

namespace App\Domains\Agenda\DTOs;

use App\Domains\Agenda\Enums\WaitlistPeriod;
use App\Domains\Agenda\Enums\WaitlistStatus;
use Carbon\CarbonImmutable;

readonly class WaitlistData
{
    /** What the patient waits for and, on edits, the status set by the reception. */
    public function __construct(
        public int $patientId,
        public int $procedureId,
        public ?int $professionalId,
        public CarbonImmutable $fromDate,
        public CarbonImmutable $toDate,
        public WaitlistPeriod $period,
        public ?string $notes,
        public ?WaitlistStatus $status,
    ) {}
}
