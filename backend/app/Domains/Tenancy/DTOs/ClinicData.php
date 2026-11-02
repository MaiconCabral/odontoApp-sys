<?php

namespace App\Domains\Tenancy\DTOs;

readonly class ClinicData
{
    /** Editable clinic registration data. */
    public function __construct(
        public string $name,
        public string $unit,
        public ?string $cnpj,
        public ?string $phone,
        public ?string $email,
        public ?string $address,
    ) {}
}
