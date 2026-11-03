<?php

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Support\PatientLinks;
use App\Domains\Platform\Models\PlatformAdmin;
use App\Domains\Tenancy\Models\Plan;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->clinic = $this->createClinic('sorriso');
    $this->other = $this->createClinic('outra');
    $this->free = Plan::query()->firstOrCreate(['code' => 'free'], ['name' => 'Free', 'price_cents' => 0, 'max_professionals' => 1]);
    $this->admin = PlatformAdmin::create(['name' => 'Equipe', 'email' => 'equipe@plataforma.app', 'password' => 'Plataforma123']);

    $this->clinic->run(function () {
        // One admin who does not attend and two professionals.
        $this->clinicAdmin = User::factory()->admin()->create(['email' => 'equipe@plataforma.app', 'password' => 'Plataforma123', 'attends_patients' => false]);
        $professional = User::factory()->count(2)->create()->first();
        Appointment::factory()->at(now()->subDays(3)->setTime(9, 0)->toDateTimeString())->create(['professional_id' => $professional->id]);
    });

    $this->signInPlatform = fn () => $this->centralRequest('POST', 'platform/login', ['email' => 'equipe@plataforma.app', 'password' => 'Plataforma123'])->assertOk();
});

describe('session', function () {
    it('signs the platform admin in on the central domain', function () {
        ($this->signInPlatform)()->assertJsonPath('admin.name', 'Equipe');

        $this->centralRequest('GET', 'platform/session')->assertOk()->assertJsonPath('admin.email', 'equipe@plataforma.app');
        $this->centralRequest('POST', 'platform/logout')->assertNoContent();
        $this->centralRequest('GET', 'platform/session')->assertUnauthorized();
    });

    it('refuses a wrong password', function () {
        $this->centralRequest('POST', 'platform/login', ['email' => 'equipe@plataforma.app', 'password' => 'errada'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'E-mail ou senha incorretos.');
    });

    it('keeps clinic users out of the platform and the platform out of the clinics', function () {
        // A clinic admin with the same e-mail and password is still not a platform admin.
        $this->signIn('sorriso', $this->clinicAdmin, 'Plataforma123');
        $this->centralRequest('GET', 'platform/clinics')->assertUnauthorized();

        $this->flushSession();
        ($this->signInPlatform)();
        $this->clinicRequest('sorriso', 'GET', 'users')->assertUnauthorized();
    });

    it('creates the admin from the command, asking the password', function () {
        $this->artisan('platform:create-admin', ['email' => 'Nova@Plataforma.app', '--name' => 'Nova'])
            ->expectsQuestion('Senha (mínimo de 12 caracteres)', 'SenhaForte12345')
            ->expectsOutput('Administrador nova@plataforma.app criado.')
            ->assertSuccessful();

        $this->artisan('platform:create-admin', ['email' => 'curta@plataforma.app', '--name' => 'Curta'])
            ->expectsQuestion('Senha (mínimo de 12 caracteres)', 'curta')
            ->assertFailed();

        expect(PlatformAdmin::query()->where('email', 'nova@plataforma.app')->exists())->toBeTrue()
            ->and(PlatformAdmin::query()->where('email', 'curta@plataforma.app')->exists())->toBeFalse();
    });
});

describe('clinics', function () {
    beforeEach(fn () => ($this->signInPlatform)());

    it('lists clinics with plan, status and usage', function () {
        $rows = collect($this->centralRequest('GET', 'platform/clinics')->assertOk()->assertJsonPath('meta.total', 2)->json('data'))->keyBy('slug');

        expect($rows['sorriso'])->toMatchArray(['status' => 'trial', 'trialDaysLeft' => 14])
            ->and($rows['sorriso']['plan']['code'])->toBe('pro')
            ->and($rows['sorriso']['usage'])->toBe(['activeUsers' => 3, 'professionals' => 2, 'appointmentsLast30Days' => 1])
            ->and($rows['outra']['usage']['activeUsers'])->toBe(0);
    });

    it('searches by name or address and filters by status', function () {
        $this->centralRequest('GET', 'platform/clinics?search=outra')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'outra');

        $this->other->update(['suspended_at' => now()->toIso8601String()]);

        $this->centralRequest('GET', 'platform/clinics?status=suspended')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'outra');
        $this->centralRequest('GET', 'platform/clinics?status=trial')->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'sorriso');
    });

    it('changes the plan, ends the trial and suspends', function () {
        $this->centralRequest('PUT', "platform/clinics/{$this->clinic->id}", [
            'plan_id' => $this->free->id,
            'trial_ends_at' => null,
            'suspended' => true,
        ])->assertOk()->assertJsonPath('plan.code', 'free')->assertJsonPath('status', 'suspended');

        $clinic = $this->clinic->fresh();

        expect($clinic->plan_id)->toBe($this->free->id)
            ->and($clinic->trial_ends_at)->toBeNull()
            ->and($clinic->isSuspended())->toBeTrue()
            ->and($clinic->name)->toBe('Clínica sorriso');
    });
});

describe('suspension', function () {
    it('closes the panel, the login and the booking page, but not the patient links', function () {
        $token = $this->clinic->run(fn () => app(PatientLinks::class)->token(
            Appointment::factory()->at(now()->addDays(2)->setTime(9, 0)->toDateTimeString())->create(),
        ));
        $this->clinic->update(['suspended_at' => now()->toIso8601String()]);

        $this->clinicRequest('sorriso', 'POST', 'login', ['email' => 'equipe@plataforma.app', 'password' => 'Plataforma123'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Clínica suspensa. Fale com o suporte.');
        $this->clinicRequest('sorriso', 'GET', 'public/booking')->assertForbidden();
        $this->clinicRequest('sorriso', 'GET', "public/appointments/{$token}")->assertOk();

        // Another visitor: the session of the first clinic is not carried over.
        $this->flushSession();
        $this->clinicRequest('outra', 'GET', 'public/booking')->assertOk();
    });
});

describe('plans', function () {
    beforeEach(fn () => ($this->signInPlatform)());

    it('lists plans with how many clinics use them', function () {
        $plans = collect($this->centralRequest('GET', 'platform/plans')->assertOk()->json('data'))->keyBy('code');

        expect($plans['pro']['clinics'])->toBe(2)
            ->and($plans['free']['clinics'])->toBe(0);
    });

    it('adds, edits and deletes a plan nobody uses', function () {
        $id = $this->centralRequest('POST', 'platform/plans', ['code' => 'clinica-grande', 'name' => 'Grande', 'price_cents' => 99900, 'max_professionals' => null])
            ->assertCreated()->assertJsonPath('maxProfessionals', null)->json('id');

        $this->centralRequest('PUT', "platform/plans/{$id}", ['code' => 'clinica-grande', 'name' => 'Grande+', 'price_cents' => 109900, 'max_professionals' => 20])
            ->assertOk()->assertJsonPath('name', 'Grande+');

        $this->centralRequest('POST', 'platform/plans', ['code' => 'Clinica Grande', 'name' => 'X', 'price_cents' => -1])
            ->assertJsonValidationErrors(['code', 'price_cents']);

        $this->centralRequest('DELETE', "platform/plans/{$id}")->assertNoContent();
    });

    it('refuses deleting a plan in use or the trial plan', function () {
        $pro = Plan::query()->where('code', Plan::TRIAL_CODE)->first();

        $this->centralRequest('DELETE', "platform/plans/{$pro->id}")->assertJsonValidationErrors(['plan' => 'O plano de trial não pode ser excluído.']);

        $this->clinic->update(['plan_id' => $this->free->id]);
        $this->centralRequest('DELETE', "platform/plans/{$this->free->id}")->assertJsonValidationErrors(['plan']);
    });
});
