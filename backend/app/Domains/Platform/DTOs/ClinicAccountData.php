<?php

namespace App\Domains\Platform\DTOs;

use Carbon\CarbonImmutable;

readonly class ClinicAccountData
{
    /** Plan, end of the trial (null once the clinic pays) and suspension of a clinic. */
    public function __construct(
        public int $planId,
        public ?CarbonImmutable $trialEndsAt,
        public bool $suspended,
    ) {}
}
