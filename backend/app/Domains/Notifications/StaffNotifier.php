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
    /** Alerts the staff about an appointment once the current transaction commits. */
    public function send(Appointment $appointment, StaffMessage $message): void
    {
        DB::afterCommit(fn () => $this->dispatch($appointment, $message));
    }

    /** Writes the alert for every active user who answers for the appointment. */
    private function dispatch(Appointment $appointment, StaffMessage $message): void
    {
        $appointment->loadMissing('patient');
        $recipients = $this->recipients($appointment, $message);

        if ($recipients->isEmpty()) {
            return;
        }

        $when = PatientNotifier::when($appointment->starts_at);

        Notification::send($recipients, new StaffAlert($message, [
            'appointmentId' => (string) $appointment->id,
            'date' => $appointment->starts_at->toDateString(),
            'title' => $message->title(),
            'body' => $message->body($appointment->patient->name, $when),
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
