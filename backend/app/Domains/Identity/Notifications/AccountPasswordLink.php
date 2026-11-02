<?php

namespace App\Domains\Identity\Notifications;

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Support\ClinicUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// Queued so the response time does not reveal whether the e-mail has an account.
class AccountPasswordLink extends Notification implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    /** Keeps the link and whether it is an invitation. */
    public function __construct(
        #[\SensitiveParameter] public readonly string $url,
        public readonly bool $isInvitation = false,
    ) {}

    /** Builds the password link on the current clinic. */
    public static function urlFor(User $user, #[\SensitiveParameter] string $token): string
    {
        return ClinicUrl::to(tenant(), '/redefinir-senha', [
            'token' => $token,
            'email' => $user->email,
        ]);
    }

    /**
     * Delivers by e-mail.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /** Picks the invitation or the reset message. */
    public function toMail(object $notifiable): MailMessage
    {
        return $this->isInvitation ? $this->invitation() : $this->reset();
    }

    /** E-mail for a user added by an admin. */
    private function invitation(): MailMessage
    {
        return (new MailMessage)
            ->subject('Você foi adicionado à equipe')
            ->greeting('Olá!')
            ->line('Sua conta foi criada. Defina sua senha para acessar o painel da clínica.')
            ->action('Definir senha', $this->url)
            ->line('O link expira em breve. Se isso acontecer, use "Esqueci minha senha" na tela de login.');
    }

    /** E-mail for a forgotten password. */
    private function reset(): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefinição de senha')
            ->greeting('Olá!')
            ->line('Recebemos um pedido para redefinir a senha da sua conta.')
            ->action('Redefinir senha', $this->url)
            ->line('Se você não fez esse pedido, ignore este e-mail.');
    }
}
