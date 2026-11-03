<?php

namespace App\Domains\Agenda\Enums;

use Carbon\CarbonInterface;

enum WaitlistPeriod: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Any = 'any';

    /** Name of the period shown to the reception. */
    public function label(): string
    {
        return match ($this) {
            self::Morning => 'Manhã',
            self::Afternoon => 'Tarde',
            self::Any => 'Qualquer horário',
        };
    }

    /** Whether a start time falls in the period. Morning ends at noon. */
    public function includes(CarbonInterface $start): bool
    {
        return match ($this) {
            self::Morning => $start->hour < 12,
            self::Afternoon => $start->hour >= 12,
            self::Any => true,
        };
    }
}
