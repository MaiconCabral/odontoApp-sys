<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Enums\StaffMessage;
use Illuminate\Notifications\Notification;

// Alert shown to the clinic staff under the bell. It is a row in the clinic database,
// written right away, so it needs no queue.
class StaffAlert extends Notification
{
    /**
     * Keeps the alert type and the appointment snapshot.
     *
     * @param  array{appointmentId: string, date: string, title: string, body: string}  $details
     */
    public function __construct(
        public readonly StaffMessage $message,
        public readonly array $details,
    ) {}

    /**
     * Stored in the notifications table only.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** Type stored in the notifications table. */
    public function databaseType(object $notifiable): string
    {
        return $this->message->value;
    }

    /**
     * Data read by the bell.
     *
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return $this->details;
    }
}
