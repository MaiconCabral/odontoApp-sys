<?php

use App\Domains\Platform\Models\PlatformAdmin;
use App\Domains\Tenancy\Models\Plan;
use App\Domains\Tenancy\Models\Tenant;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Storage;

// Sets an env value the seeders read with env(), and remembers it to undo after the test.
function demoEnv(string $key, ?string $value): void
{
    if ($value === null) {
        putenv($key);
        unset($_ENV[$key], $_SERVER[$key]);

        return;
    }

    putenv("{$key}={$value}");
    $_ENV[$key] = $_SERVER[$key] = $value;
}

beforeEach(function () {
    Storage::fake('local');
    demoEnv('SEED_DEMO_PASSWORD', 'Sorriso123');
});

afterEach(function () {
    // The demo clinics are not created through createClinic, so they are removed here.
    tenancy()->end();
    Tenant::query()->get()->each->delete();

    foreach (['SEED_DEMO_PASSWORD', 'PLATFORM_ADMIN_EMAIL', 'PLATFORM_ADMIN_PASSWORD'] as $key) {
        demoEnv($key, null);
    }
});

describe('demo:reset', function () {
    it('refuses to run outside demo mode', function () {
        config(['app.demo' => false]);
        $visitor = $this->createClinic('visitante');

        $this->artisan('demo:reset')->assertFailed();

        expect(Tenant::find($visitor->id))->not->toBeNull();
    });

    it('wipes the clinics of visitors and their files, and seeds the demo clinics again', function () {
        config(['app.demo' => true]);
        $visitor = $this->createClinic('visitante');
        Storage::disk('local')->put("clinics/{$visitor->id}/patients/1/exame.pdf", 'pdf');

        $this->artisan('demo:reset')->assertSuccessful();

        expect(Tenant::find($visitor->id))->toBeNull()
            ->and(Storage::disk('local')->allFiles('clinics'))->toBe([])
            ->and(Tenant::query()->pluck('id')->sort()->values()->all())->toBe(['demo-clinica-horizonte', 'demo-sorriso-perfeito'])
            ->and(Plan::query()->where('code', Plan::TRIAL_CODE)->exists())->toBeTrue();
    });

    it('signs the demo users in after the reset', function () {
        config(['app.demo' => true]);

        $this->artisan('demo:reset')->assertSuccessful();

        $this->clinicRequest('sorriso-perfeito', 'POST', 'login', ['email' => 'demo@sorriso.app', 'password' => 'Sorriso123'])->assertOk();
    });
});

describe('seeders', function () {
    it('creates the demo clinics in demo mode', function () {
        config(['app.demo' => true]);

        $this->seed(DatabaseSeeder::class);

        expect(Tenant::query()->count())->toBe(2);
    });

    it('creates only the plans outside local and demo', function () {
        config(['app.demo' => false]);

        $this->seed(DatabaseSeeder::class);

        expect(Tenant::query()->count())->toBe(0)
            ->and(Plan::query()->count())->toBeGreaterThan(0);
    });

    it('creates the platform admin from the server values', function () {
        demoEnv('PLATFORM_ADMIN_EMAIL', 'Equipe@Odonto.app');
        demoEnv('PLATFORM_ADMIN_PASSWORD', 'SenhaForte2026');

        $this->seed(PlatformAdminSeeder::class);

        expect(PlatformAdmin::query()->where('email', 'equipe@odonto.app')->exists())->toBeTrue();
    });

    it('skips the platform admin without the server values', function () {
        $this->seed(PlatformAdminSeeder::class);

        expect(PlatformAdmin::query()->count())->toBe(0);
    });

    it('refuses a weak platform admin password', function () {
        demoEnv('PLATFORM_ADMIN_EMAIL', 'equipe@odonto.app');
        demoEnv('PLATFORM_ADMIN_PASSWORD', 'curta');

        expect(fn () => $this->seed(PlatformAdminSeeder::class))->toThrow(RuntimeException::class);
    });
});

describe('schedule', function () {
    it('runs the reset every night at 04:00, only in demo mode', function () {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'demo:reset'));

        expect($event)->not->toBeNull()
            ->and($event->expression)->toBe('0 4 * * *')
            ->and($event->timezone)->toBe('America/Sao_Paulo');

        config(['app.demo' => false]);
        expect($event->filtersPass(app()))->toBeFalse();

        config(['app.demo' => true]);
        expect($event->filtersPass(app()))->toBeTrue();
    });
});
