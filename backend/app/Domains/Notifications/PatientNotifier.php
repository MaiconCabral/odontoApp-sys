<?php

namespace App\Domains\Notifications;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Notifications\Enums\PatientMessage;
use App\Domains\Notifications\Models\NotificationLog;
use App\Domains\Notifications\Notifications\AppointmentMessage;
use App\Domains\Notifications\Support\PatientChannels;
use App\Domains\Notifications\Support\PatientLinks;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PatientNotifier
{
    /** Receives the patient channels and the link signer. */
    public function __construct(
        private readonly PatientChannels $channels,
        private readonly PatientLinks $links,
    ) {}

    /**
     * Sends a message about an appointment once the current transaction commits.
     * Nothing goes out for a change that was rolled back.
     *
     * @param  array<string, string|null>  $extra
     */
    public function send(Appointment $appointment, PatientMessage $message, array $extra = []): void
    {
        DB::afterCommit(fn () => $this->dispatch($appointment, $message, $extra));
    }

    /**
     * Logs the attempt and queues the message, or logs why it was skipped.
     *
     * @param  array<string, string|null>  $extra
     */
    private function dispatch(Appointment $appointment, PatientMessage $message, array $extra): void
    {
        $appointment->loadMissing(['patient', 'professional', 'procedure']);
        $channels = $this->channels->for($appointment->patient);

        $log = NotificationLog::create([
            'appointment_id' => $appointment->id,
            'type' => $message,
            'channel' => $channels[0] ?? 'mail',
            'status' => $channels ? NotificationLog::STATUS_QUEUED : NotificationLog::STATUS_SKIPPED,
        ]);

        if (! $channels) {
            return;
        }

        $appointment->patient->notify(new AppointmentMessage($message, $this->details($appointment, $message, $extra), $channels, $log->id));
    }

    /**
     * Snapshot of the appointment for the message text.
     *
     * @param  array<string, string|null>  $extra
     * @return array<string, string|null>
     */
    private function details(Appointment $appointment, PatientMessage $message, array $extra): array
    {
        $clinic = tenant();

        return [
            'clinic' => $clinic->name,
            'address' => $clinic->address,
            'phone' => $clinic->phone,
            'patient' => strtok($appointment->patient->name, ' '),
            'procedure' => $appointment->procedure->name,
            'professional' => $appointment->professional?->name ?? 'a equipe da clínica',
            'when' => self::when($appointment->starts_at),
            'reason' => $appointment->cancel_reason,
            'link' => $message->hasPatientLinks() ? $this->links->url($appointment) : null,
            ...$extra,
        ];
    }

    /** Date and time as "terça-feira, 10/11 às 09:00". */
    public static function when(\DateTimeInterface $startsAt): string
    {
        $date = CarbonImmutable::instance($startsAt)->locale('pt_BR');

        return $date->isoFormat('dddd, DD/MM [às] HH:mm');
    }
}
