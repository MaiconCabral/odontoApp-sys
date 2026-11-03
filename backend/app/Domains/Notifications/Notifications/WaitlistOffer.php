<?php

namespace App\Domains\Notifications\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// E-mail to a patient of the waiting list: a time is free, book it from the link.
class WaitlistOffer extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    /**
     * Keeps a snapshot of the offer.
     *
     * @param  array<string, string|null>  $details
     */
    public function __construct(public readonly array $details) {}

    /**
     * Sent by e-mail only; the offer itself only reaches patients with an e-mail.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** E-mail with the clinic as the sender name and the booking link. */
    public function toMail(object $notifiable): MailMessage
    {
        $details = $this->details;

        $mail = (new MailMessage)
            ->from((string) config('mail.from.address'), $details['clinic'])
            ->subject("Abriu um horário para você - {$details['clinic']}")
            ->greeting("Olá, {$details['patient']}!")
            ->line("Você está na nossa lista de espera para {$details['procedure']}, e abriu um horário {$details['when']}.")
            ->line('Outras pessoas da lista também foram avisadas. O horário fica com quem pedir primeiro.')
            ->action('Quero este horário', $details['link']);

        if ($details['phone']) {
            $mail->line("Dúvidas? Fale com a clínica pelo {$details['phone']}.");
        }

        return $mail->salutation("Até breve,\n{$details['clinic']}");
    }
}
