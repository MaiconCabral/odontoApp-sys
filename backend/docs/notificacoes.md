# Notificações ao paciente

O paciente recebe um e-mail a cada mudança relevante na consulta e um lembrete na véspera. O envio fica em fila, e cada tentativa é registrada. Os endpoints estão em [api.md](api.md#links-do-paciente-subdomínio-sem-sessão).

## Mensagens

| Tipo (`PatientMessage`) | Quando | Disparo | Links do paciente |
| --- | --- | --- | --- |
| `requested`: Pedido recebido | Pedido online gravado | `RequestAppointment` | Não |
| `scheduled`: Consulta agendada | Recepção marca a consulta ou aceita o pedido | `ScheduleAppointment`, `AcceptAppointmentRequest` | Sim |
| `rescheduled`: Consulta remarcada | Recepção remarca, com a data antiga e a nova | `RescheduleAppointment` | Sim |
| `cancelled`: Consulta cancelada | A clínica cancelou, com o motivo | `ChangeAppointmentStatus` | Não |
| `rejected`: Pedido recusado | A clínica recusou o pedido, com o motivo | `ChangeAppointmentStatus` | Não |
| `reminder`: Lembrete de 24h | Na véspera, pelo agendador | `SendDueReminders` | Sim |

- O remetente tem o nome da clínica. O texto inclui data e hora por extenso ("terça-feira, 10/11 às 09:00"), procedimento, profissional, endereço e telefone.
- Quando o próprio paciente cancela ou confirma pelo link, ele não recebe e-mail, porque a mudança não tem autor.
- O texto é montado com os dados do momento do envio. Assim, a mensagem na fila não muda se a consulta mudar depois.

## Como o envio funciona

```
Action (dentro de DB::transaction)
  → PatientNotifier::send()            espera o commit (DB::afterCommit)
  → PatientChannels::for($patient)     hoje: ['mail'] se houver e-mail
  → notification_logs (queued ou skipped)
  → AppointmentMessage (ShouldQueue)   fila "database", no banco central
  → worker: QueueTenancyBootstrapper reabre a clínica → envia
  → listener NotificationSent → sent  |  failed() → failed
```

- **Depois do commit:** se a transação for desfeita, como num conflito de horário, nada é enviado.
- **Registro (`notification_logs` no banco da clínica):**
  - campos: consulta, tipo, canal, situação e data;
  - situações: `queued`, `sent`, `skipped` (paciente sem e-mail) e `failed` (depois das tentativas da fila);
  - o modal de detalhes da consulta mostra esse registro em "Avisos ao paciente".
- **Fila:**
  - `QUEUE_CONNECTION=database`;
  - a conexão da fila é fixa na central (`DB_QUEUE_CONNECTION`, que por padrão é `DB_CONNECTION`). Sem isso, um job despachado dentro de uma requisição de clínica iria para o banco da clínica;
  - a mensagem é criptografada na fila (`ShouldBeEncrypted`), porque leva nome e dados do paciente.
- **E-mail em dev:** `MAIL_MAILER=log`. As mensagens aparecem em `storage/logs/laravel.log`.

## Lembrete de 24h

O comando `appointments:send-reminders` roda a cada 15 minutos (`routes/console.php`, com `withoutOverlapping`). Ele percorre todas as clínicas e, em cada uma:

1. busca as consultas Agendadas ou Confirmadas que começam entre agora + 1h e agora + 24h e ainda não têm `reminder_sent_at`;
2. marca `reminder_sent_at` com `update ... whereNull` antes de enviar, para que duas execuções nunca mandem o mesmo lembrete;
3. envia o lembrete com os links do paciente.

Regras:

- Uma consulta marcada com menos de 1h de antecedência não recebe lembrete.
- Remarcar zera `reminder_sent_at`, e o lembrete vale para a nova data.
- O comando mostra quantos lembretes enviou por clínica.

## Links do paciente

Os e-mails `scheduled`, `rescheduled` e `reminder` trazem um link para `/consulta?token=...` no subdomínio da clínica. Pela página, sem login, o paciente confirma a presença ou cancela a consulta.

- **Formato do token:** `{id}-{hmac}`. O HMAC-SHA256, feito com a `APP_KEY`, assina a clínica, o id da consulta e o horário de início. Nada fica gravado no banco.
- **Quando para de funcionar:**
  - no início da consulta;
  - quando a consulta é remarcada, porque o horário assinado muda;
  - em outra clínica, porque a clínica faz parte da assinatura.
- **Link inválido:** responde `404`, com "Este link é inválido ou expirou."
- **Registro no histórico:** a mudança entra sem autor, aparece como "Paciente" e leva o motivo "Confirmado pelo paciente pelo link." ou "Cancelado pelo paciente pelo link."
- **Limites:** 60 consultas por minuto e 10 confirmações ou cancelamentos por minuto, por IP e clínica.

Trocar a `APP_KEY` invalida todos os links já enviados.

> **Mudança em relação ao plano:** o plano previa um token aleatório guardado como hash em `appointments.patient_token_hash`. O HMAC dá a mesma garantia sem coluna nova e expira sozinho quando a consulta muda.

## Como ligar o WhatsApp

As actions não precisam mudar. Os passos são:

1. Criar o canal de notificação, por exemplo `App\Domains\Notifications\Channels\WhatsAppChannel`, que chama o provedor.
2. Em `PatientChannels::for()`, devolver esse canal quando o paciente tiver telefone e tiver autorizado o contato.
3. Em `AppointmentMessage`, adicionar `toWhatsApp()` com o texto curto de cada tipo.
4. O listener `NotificationSent` já registra o envio pelo canal.

Hoje `notification_logs` guarda uma linha por mensagem, com o canal principal. Com dois canais, passe a criar uma linha por canal.

## Rodar em dev (Windows)

`composer dev` sobe API, fila, agendador e Vite juntos. Para rodar só o necessário, use três terminais em `backend/`:

```bash
php artisan serve                       # API em 127.0.0.1:8000
php artisan queue:work --tries=3        # envia as mensagens da fila
php artisan schedule:work               # roda o lembrete a cada 15 minutos
```

Para testar sem esperar o agendador, rode `php artisan appointments:send-reminders`. Depois de mudar código das mensagens, reinicie o `queue:work`, porque o worker mantém o código antigo carregado.

## Produção

- Cron de cada minuto: `* * * * * php /caminho/artisan schedule:run`.
- Worker de fila supervisionado, como Supervisor ou systemd, com `queue:work --tries=3` e `queue:restart` a cada deploy.
- Um mailer real (`MAIL_MAILER`, `MAIL_FROM_ADDRESS`).
