<?php

namespace App\Domains\Notifications\Enums;

enum StaffMessage: string
{
    case BookingRequested = 'booking_requested';
    case CancelledByPatient = 'cancelled_by_patient';
    case ConfirmedByPatient = 'confirmed_by_patient';
    case ReminderFailed = 'reminder_failed';

    /** Title of the alert under the bell. */
    public function title(): string
    {
        return match ($this) {
            self::BookingRequested => 'Novo pedido de agendamento',
            self::CancelledByPatient => 'Paciente cancelou a consulta',
            self::ConfirmedByPatient => 'Paciente confirmou a consulta',
            self::ReminderFailed => 'Falha no lembrete de 24h',
        };
    }

    /** Text of the alert, with the patient and the time. */
    public function body(string $patient, string $when): string
    {
        return match ($this) {
            self::BookingRequested => "{$patient} pediu um horário para {$when}.",
            self::CancelledByPatient => "{$patient} cancelou a consulta de {$when}.",
            self::ConfirmedByPatient => "{$patient} confirmou a consulta de {$when}.",
            self::ReminderFailed => "Não foi possível enviar o lembrete de {$patient} para {$when}.",
        };
    }

    /** Requests only concern who answers them; patient answers also reach the professional. */
    public function reachesProfessional(): bool
    {
        return $this !== self::BookingRequested;
    }
}
