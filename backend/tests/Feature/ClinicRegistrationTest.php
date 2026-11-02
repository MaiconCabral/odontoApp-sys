<?php

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Actions\RegisterClinic;
use App\Domains\Tenancy\DTOs\RegisterClinicData;
use App\Domains\Tenancy\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Stancl\Tenancy\Database\Models\Domain;

function clinicPayload(array $overrides = []): array
{
    return [
        'name' => 'Marina Lopes',
        'email' => 'Marina@Clinica.App',
        'password' => 'Sorriso123',
        'password_confirmation' => 'Sorriso123',
        'clinic_name' => 'Clínica São José',
        'unit' => '',
        ...$overrides,
    ];
}

beforeEach(function () {
    $this->createPlan();
});

afterEach(function () {
    Tenant::all()->each(fn (Tenant $tenant) => $tenant->delete());
});

it('creates the clinic, its database and its administrator', function () {
    $response = $this->centralRequest('POST', 'clinics', clinicPayload())
        ->assertCreated()
        ->assertJsonPath('slug', 'clinica-sao-jose');

    expect($response->json('loginUrl'))->toContain('http://clinica-sao-jose.localhost:3000/login');

    $tenant = Tenant::query()->sole();

    expect($tenant->unit)->toBe('Unidade principal')
        ->and($tenant->plan->code)->toBe('pro')
        ->and($tenant->trialDaysLeft())->toBe(14);

    $admin = $tenant->run(fn () => User::query()->sole());

    expect($admin->email)->toBe('marina@clinica.app')
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($admin->attends_patients)->toBeTrue()
        ->and(Hash::check('Sorriso123', $admin->password))->toBeTrue();
});

it('adds a number to a repeated slug', function () {
    $this->centralRequest('POST', 'clinics', clinicPayload())->assertCreated();

    $this->centralRequest('POST', 'clinics', clinicPayload())
        ->assertCreated()
        ->assertJsonPath('slug', 'clinica-sao-jose-2');
});

it('never gives a clinic a reserved slug', function () {
    $this->centralRequest('POST', 'clinics', clinicPayload(['clinic_name' => 'Admin']))
        ->assertCreated()
        ->assertJsonPath('slug', 'admin-2');
});

it('repeats the password rules of the frontend', function (string $password) {
    $this->centralRequest('POST', 'clinics', clinicPayload(['password' => $password, 'password_confirmation' => $password]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password' => 'Use ao menos 8 caracteres, com letras e números.']);
})->with(['curta1', 'semnumeros', '12345678']);

it('validates the required fields', function () {
    $this->centralRequest('POST', 'clinics', clinicPayload(['name' => 'M', 'clinic_name' => '', 'email' => 'invalido']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'clinic_name', 'email']);
});

it('removes the clinic when the setup fails midway', function () {
    $invalid = new RegisterClinicData(
        name: 'Marina',
        email: 'marina@clinica.app',
        password: 'Sorriso123',
        clinicName: 'Clínica Falha',
        unit: null,
    );

    Domain::creating(fn () => throw new RuntimeException('falha simulada'));

    expect(fn () => app(RegisterClinic::class)->handle($invalid))->toThrow(RuntimeException::class);
    expect(Tenant::query()->count())->toBe(0);
});

it('is not available from a clinic host', function () {
    $clinic = $this->createClinic('sorriso');

    $this->clinicRequest('sorriso', 'POST', 'clinics', clinicPayload())->assertStatus(404);
    expect(Tenant::query()->count())->toBe(1);
});
