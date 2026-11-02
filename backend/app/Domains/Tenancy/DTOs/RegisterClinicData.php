<?php

namespace App\Domains\Tenancy\DTOs;

readonly class RegisterClinicData
{
    /** Data typed in the clinic registration. */
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $clinicName,
        public ?string $unit,
    ) {}
}
