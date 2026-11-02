<?php

namespace App\Domains\Agenda\Enums;

enum AppointmentStatus: string
{
    case Requested = 'requested';
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case NoShow = 'no_show';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';

    /** Name of the status shown to users. */
    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Solicitado',
            self::Scheduled => 'Agendado',
            self::Confirmed => 'Confirmado',
            self::Completed => 'Concluído',
            self::NoShow => 'Faltou',
            self::Cancelled => 'Cancelado',
            self::Rejected => 'Recusado',
        };
    }

    /**
     * Statuses this one may move to.
     *
     * @return list<self>
     */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::Requested => [self::Scheduled, self::Rejected, self::Cancelled],
            self::Scheduled => [self::Confirmed, self::Completed, self::NoShow, self::Cancelled],
            self::Confirmed => [self::Completed, self::NoShow, self::Cancelled],
            default => [],
        };
    }

    /** Tells whether the move to the given status is allowed. */
    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->nextStatuses(), true);
    }

    /** Tells whether an appointment in this status holds its time. */
    public function occupiesSlot(): bool
    {
        return in_array($this, self::occupying(), true);
    }

    /** Tells whether this status needs a reason. */
    public function requiresReason(): bool
    {
        return in_array($this, [self::Cancelled, self::Rejected], true);
    }

    /** Tells whether this status only makes sense after the appointment started. */
    public function requiresStart(): bool
    {
        return in_array($this, [self::Completed, self::NoShow], true);
    }

    /**
     * Statuses that hold a time in the agenda.
     *
     * @return list<self>
     */
    public static function occupying(): array
    {
        return [self::Requested, self::Scheduled, self::Confirmed];
    }
}
