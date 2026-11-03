<?php

namespace App\Domains\Notifications;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Enums\StaffMessage;
use App\Domains\Notifications\Notifications\StaffAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class StaffNotifier
{
    /**
     * Alerts the staff about an appointment once the current transaction commits.
     * $who and $at replace the patient and the time of the appointment in the text,
     * for alerts about other people or about a time the appointment no longer holds.
     */
    public function send(Appointment $appointment, StaffMessage $message, ?string $who = null, ?\DateTimeInterface $at = null): void
    {
        DB::afterCommit(fn () => $this->dispatch($appointment, $message, $who, $at));
    }

    /** Writes the alert for every active user who answers for the appointment. */
    private function dispatch(Appointment $appointment, StaffMessage $message, ?string $who, ?\DateTimeInterface $at): void
    {
        $appointment->loadMissing('patient');
        $recipients = $this->recipients($appointment, $message);

        if ($recipients->isEmpty()) {
            return;
        }

        $start = $at ?? $appointment->starts_at;

        Notification::send($recipients, new StaffAlert($message, [
            'appointmentId' => (string) $appointment->id,
            'date' => $start->format('Y-m-d'),
            'title' => $message->title(),
            'body' => $message->body($who ?? $appointment->patient->name, PatientNotifier::when($start)),
        ]));
    }

    /**
     * Admins and reception, plus the professional when the alert is about their patient.
     *
     * @return Collection<int, User>
     */
    private function recipients(Appointment $appointment, StaffMessage $message): Collection
    {
        return User::query()
            ->where('active', true)
            ->where(function ($query) use ($appointment, $message) {
                $query->whereIn('role', [UserRole::Admin, UserRole::Reception]);

                if ($message->reachesProfessional() && $appointment->professional_id) {
                    $query->orWhere('id', $appointment->professional_id);
                }
            })
            ->get();
    }
}
