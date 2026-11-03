<?php

namespace App\Domains\Notifications\Actions;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Notifications\Enums\PatientMessage;
use App\Domains\Notifications\PatientNotifier;

class SendDueReminders
{
    public const HOURS_AHEAD = 24;

    public const MIN_MINUTES_AHEAD = 60;

    /** Receives the patient notifier. */
    public function __construct(private readonly PatientNotifier $notifier) {}

    /**
     * Sends the reminder of every appointment starting in the next 24 hours, once.
     * Appointments less than an hour away are left out: the reminder would arrive too late.
     */
    public function handle(): int
    {
        $sent = 0;

        $due = Appointment::query()
            ->whereIn('status', [AppointmentStatus::Scheduled, AppointmentStatus::Confirmed])
            ->whereNull('reminder_sent_at')
            ->whereBetween('starts_at', [now()->addMinutes(self::MIN_MINUTES_AHEAD), now()->addHours(self::HOURS_AHEAD)])
            ->with(['patient', 'professional', 'procedure'])
            ->get();

        foreach ($due as $appointment) {
            // Claims the appointment first, so two runs at the same time never send it twice.
            $claimed = Appointment::query()->whereKey($appointment->id)->whereNull('reminder_sent_at')->update(['reminder_sent_at' => now()]);

            if ($claimed) {
                $this->notifier->send($appointment, PatientMessage::Reminder);
                $sent++;
            }
        }

        return $sent;
    }
}
