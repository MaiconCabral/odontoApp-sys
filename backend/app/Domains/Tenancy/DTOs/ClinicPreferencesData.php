<?php

namespace App\Domains\Tenancy\DTOs;

readonly class ClinicPreferencesData
{
    /** Service preferences of the clinic. */
    public function __construct(
        public bool $autoConfirmation,
        public bool $waitingList,
        public bool $reminder24h,
    ) {}
}
