<?php

namespace App\Domains\Agenda\DTOs;

use App\Domains\Agenda\Models\Procedure;
use Carbon\CarbonImmutable;

readonly class BookingRequestData
{
    /** Data a patient sends from the public booking page. */
    public function __construct(
        public string $name,
        public string $phone,
        public ?string $email,
        public Procedure $procedure,
        public ?int $professionalId,
        public CarbonImmutable $startsAt,
        public ?string $notes = null,
    ) {}
}
