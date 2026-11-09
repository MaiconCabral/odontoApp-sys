<?php

namespace App\Console\Commands;

use App\Domains\Clinical\Models\PatientAttachment;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResetDemo extends Command
{
    protected $signature = 'demo:reset {--force : Runs outside demo mode (local development only)}';

    protected $description = 'Apaga todas as clínicas, anexos e dados e roda migrate:fresh --seed (servidor de demonstração)';

    /** Wipes every clinic and the central database, then seeds the demo data again. */
    public function handle(): int
    {
        if (! config('app.demo') && ! ($this->option('force') && app()->environment('local', 'testing'))) {
            $this->error('demo:reset só roda com DEMO_MODE=true (ou com --force em ambiente local).');

            return self::FAILURE;
        }

        $clinics = $this->deleteClinics();
        $orphans = $this->dropOrphanDatabases();

        Storage::disk(PatientAttachment::DISK)->deleteDirectory('clinics');
        $this->callSilently('cache:clear');

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $summary = "demo:reset: {$clinics} clínica(s) e {$orphans} banco(s) órfão(s) removidos; dados de demonstração recriados.";
        Log::info($summary);
        $this->info($summary);

        return self::SUCCESS;
    }

    /** Deletes every clinic through the model, so the tenancy jobs drop their databases too. */
    private function deleteClinics(): int
    {
        tenancy()->end();

        $count = 0;

        Tenant::query()->cursor()->each(function (Tenant $tenant) use (&$count) {
            $tenant->delete();
            $count++;
        });

        return $count;
    }

    /** Drops clinic databases left without a row, e.g. after a migrate:fresh run by hand. */
    private function dropOrphanDatabases(): int
    {
        $prefix = (string) config('tenancy.database.prefix');
        $central = config('tenancy.database.central_connection');

        if (DB::connection($central)->getDriverName() === 'sqlite') {
            $files = File::glob(database_path($prefix.'*'));
            File::delete($files);

            return count($files);
        }

        $names = collect(DB::connection($central)->select('SHOW DATABASES LIKE ?', [str_replace('_', '\_', $prefix).'%']))
            ->map(fn (object $row) => array_values((array) $row)[0]);

        $names->each(fn (string $name) => DB::connection($central)->statement('DROP DATABASE `'.str_replace('`', '', $name).'`'));

        return $names->count();
    }
}
