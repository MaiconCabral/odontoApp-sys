<?php

namespace App\Domains\Agenda\Actions;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Jobs\OfferFreedSlotJob;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Enums\PatientMessage;
use App\Domains\Notifications\Enums\StaffMessage;
use App\Domains\Notifications\PatientNotifier;
use App\Domains\Notifications\StaffNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeAppointmentStatus
{
    /** Receives the patient and staff notifiers. */
    public function __construct(
        private readonly PatientNotifier $notifier,
        private readonly StaffNotifier $staff,
    ) {}

    /** Applies a status change allowed by the flow and records it. */
    public function handle(Appointment $appointment, AppointmentStatus $next, ?string $reason, ?User $author): Appointment
    {
        return DB::transaction(function () use ($appointment, $next, $reason, $author) {
            // Two people may act on the same appointment at once; the second sees the first change.
            $appointment = Appointment::query()->whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            $this->ensureAllowed($appointment, $next, $reason);

            if ($next->requiresReason()) {
                $appointment->cancel_reason = $reason;
            }

            $appointment->moveTo($next, $author, $reason);

            // The patient is told when the clinic cancels or rejects, not when the patient did it.
            if ($author && $next === AppointmentStatus::Cancelled) {
                $this->notifier->send($appointment, PatientMessage::Cancelled);
            }

            if ($author && $next === AppointmentStatus::Rejected) {
                $this->notifier->send($appointment, PatientMessage::Rejected);
            }

            // Without an author the patient answered through the link, and the clinic is told.
            if (! $author && $next === AppointmentStatus::Cancelled) {
                $this->staff->send($appointment, StaffMessage::CancelledByPatient);
            }

            if (! $author && $next === AppointmentStatus::Confirmed) {
                $this->staff->send($appointment, StaffMessage::ConfirmedByPatient);
            }

            // The time it held is free again, so the waiting list may take it.
            if (in_array($next, [AppointmentStatus::Cancelled, AppointmentStatus::Rejected], true)) {
                OfferFreedSlotJob::afterCommit($appointment, $appointment->starts_at, $appointment->minutes(), $appointment->professional_id);
            }

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
