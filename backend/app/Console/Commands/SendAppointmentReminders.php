<?php

namespace App\Console\Commands;

use App\Domains\Notifications\Actions\SendDueReminders;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    protected $description = 'Envia o lembrete de 24h das consultas de todas as clínicas';

    /** Runs the reminders in each clinic database. */
    public function handle(SendDueReminders $reminders): int
    {
        Tenant::query()->cursor()->each(function (Tenant $tenant) use ($reminders) {
            if (! $tenant->sendsReminders()) {
                $this->line("{$tenant->name}: lembretes desligados.");

                return;
            }

            $sent = $tenant->run(fn () => $reminders->handle());

            $this->line("{$tenant->name}: {$sent} lembrete(s).");
        });

        return self::SUCCESS;
    }
}
