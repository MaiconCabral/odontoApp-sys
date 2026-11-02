<?php

namespace App\Domains\Agenda\Actions;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeAppointmentStatus
{
    /** Applies a status change allowed by the flow and records it. */
    public function handle(Appointment $appointment, AppointmentStatus $next, ?string $reason, User $author): Appointment
    {
        return DB::transaction(function () use ($appointment, $next, $reason, $author) {
            // Two people may act on the same appointment at once; the second sees the first change.
            $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            $this->ensureAllowed($appointment, $next, $reason);

            if ($next->requiresReason()) {
                $appointment->cancel_reason = $reason;
            }

            $appointment->moveTo($next, $author, $reason);

            return $appointment;
        });
    }

    /** Refuses moves outside the flow, without a reason or too early. */
    private function ensureAllowed(Appointment $appointment, AppointmentStatus $next, ?string $reason): void
    {
        if ($next === AppointmentStatus::Scheduled) {
            throw ValidationException::withMessages(['status' => 'Para aceitar um pedido, escolha a sala.']);
        }

        if (! $appointment->status->canTransitionTo($next)) {
            throw ValidationException::withMessages([
                'status' => "Não é possível mudar de {$appointment->status->label()} para {$next->label()}.",
            ]);
        }

        if ($next->requiresReason() && trim((string) $reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Informe o motivo.']);
        }

        if ($next->requiresStart() && $appointment->starts_at > now()) {
            throw ValidationException::withMessages(['status' => 'Só é possível marcar isso depois do início da consulta.']);
        }
    }
}
