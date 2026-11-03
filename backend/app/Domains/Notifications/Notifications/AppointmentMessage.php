<?php

namespace App\Domains\Notifications\Notifications;

use App\Domains\Notifications\Enums\PatientMessage;
use App\Domains\Notifications\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

// Every message to a patient. It carries a snapshot of the appointment, so the queue never
// sends a newer time or name than the one the message was about.
class AppointmentMessage extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    /**
     * Keeps the message type, the appointment snapshot and the log row to update.
     *
     * @param  array<string, string|null>  $details
     */
    public function __construct(
        public readonly PatientMessage $message,
        public readonly array $details,
        public readonly array $channels,
        public readonly int $logId,
    ) {}

    /**
     * Channels chosen when the message was created.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /** E-mail with the clinic as the sender name. */
    public function toMail(object $notifiable): MailMessage
    {
        $details = $this->details;
        $mail = (new MailMessage)
            ->from((string) config('mail.from.address'), $details['clinic'])
            ->subject($this->message->subject($details['clinic']))
            ->greeting("Olá, {$details['patient']}!");

        $mail = match ($this->message) {
            PatientMessage::Requested => $mail
                ->line("Recebemos seu pedido de {$details['procedure']} para {$details['when']}.")
                ->line('A clínica vai analisar a agenda e confirmar o horário com você.'),
            PatientMessage::Scheduled => $mail
                ->line("Sua consulta de {$details['procedure']} está marcada para {$details['when']}, com {$details['professional']}."),
            PatientMessage::Rescheduled => $mail
                ->line("Sua consulta mudou de {$details['previous']} para {$details['when']}, com {$details['professional']}."),
            PatientMessage::Cancelled => $mail
                ->line("Sua consulta de {$details['when']} foi cancelada.")
                ->line("Motivo: {$details['reason']}"),
            PatientMessage::Rejected => $mail
                ->line("Não conseguimos atender o pedido de {$details['when']}.")
                ->line("Motivo: {$details['reason']}"),
            PatientMessage::Reminder => $mail
                ->line("Lembrete: sua consulta de {$details['procedure']} é {$details['when']}, com {$details['professional']}.")
                ->line('Confirme sua presença pelo botão abaixo. Se não puder vir, cancele pelo mesmo link para liberar o horário.'),
        };

        if ($this->message->hasPatientLinks() && $details['link']) {
            $mail->action($this->message === PatientMessage::Reminder ? 'Confirmar ou cancelar' : 'Ver ou cancelar consulta', $details['link']);
        }

        if ($details['address']) {
            $mail->line("Endereço: {$details['address']}");
        }

        if ($details['phone']) {
            $mail->line("Dúvidas? Fale com a clínica pelo {$details['phone']}.");
        }

        return $mail->salutation("Até breve,\n{$details['clinic']}");
    }

    /** Marks the log row as failed when the queue gives up. */
    public function failed(Throwable $exception): void
    {
        NotificationLog::query()->whereKey($this->logId)->update([
            'status' => NotificationLog::STATUS_FAILED,
            'error' => mb_substr($exception->getMessage(), 0, 250),
        ]);
    }
}
