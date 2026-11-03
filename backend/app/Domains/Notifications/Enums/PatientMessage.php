<?php

namespace App\Domains\Notifications\Enums;

enum PatientMessage: string
{
    case Requested = 'requested';
    case Scheduled = 'scheduled';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case Reminder = 'reminder';

    /** Name of the message shown to the reception. */
    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Pedido recebido',
            self::Scheduled => 'Consulta agendada',
            self::Rescheduled => 'Consulta remarcada',
            self::Cancelled => 'Consulta cancelada',
            self::Rejected => 'Pedido recusado',
            self::Reminder => 'Lembrete de 24h',
        };
    }

    /** Subject of the e-mail, with the clinic name. */
    public function subject(string $clinic): string
    {
        return match ($this) {
            self::Requested => "Recebemos seu pedido de consulta - {$clinic}",
            self::Scheduled => "Sua consulta está agendada - {$clinic}",
            self::Rescheduled => "Sua consulta mudou de horário - {$clinic}",
            self::Cancelled => "Sua consulta foi cancelada - {$clinic}",
            self::Rejected => "Não conseguimos atender seu pedido - {$clinic}",
            self::Reminder => "Lembrete: sua consulta é amanhã - {$clinic}",
        };
    }

    /** Tells whether the message offers the patient the links to confirm or cancel. */
    public function hasPatientLinks(): bool
    {
        return in_array($this, [self::Scheduled, self::Rescheduled, self::Reminder], true);
    }
}
