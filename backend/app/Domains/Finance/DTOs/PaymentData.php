<?php

namespace App\Domains\Finance\DTOs;

use App\Domains\Finance\Enums\PaymentMethod;
use Carbon\CarbonImmutable;

readonly class PaymentData
{
    /** A payment typed by the reception; allowOver accepts more than the price (an extra on the day). */
    public function __construct(
        public int $amountCents,
        public PaymentMethod $method,
        public CarbonImmutable $paidAt,
        public ?string $notes,
        public bool $allowOver,
    ) {}
}
