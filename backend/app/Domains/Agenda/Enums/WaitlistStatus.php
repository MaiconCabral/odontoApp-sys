<?php

namespace App\Domains\Agenda\Enums;

enum WaitlistStatus: string
{
    case Waiting = 'waiting';
    case Notified = 'notified';
    case Booked = 'booked';
    case Removed = 'removed';

    /** Name of the status shown to the reception. */
    public function label(): string
    {
        return match ($this) {
            self::Waiting => 'Aguardando',
            self::Notified => 'Avisado',
            self::Booked => 'Agendado',
            self::Removed => 'Removido',
        };
    }
}
