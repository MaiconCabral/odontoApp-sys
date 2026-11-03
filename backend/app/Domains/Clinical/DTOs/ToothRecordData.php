<?php

namespace App\Domains\Clinical\DTOs;

use App\Domains\Clinical\Enums\ToothCondition;
use App\Domains\Clinical\Enums\ToothSurface;

readonly class ToothRecordData
{
    /** Tooth in FDI notation, the surface (null for the whole tooth) and its condition. */
    public function __construct(
        public int $tooth,
        public ?ToothSurface $surface,
        public ToothCondition $condition,
        public ?string $notes,
    ) {}
}
