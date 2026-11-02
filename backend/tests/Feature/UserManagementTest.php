<?php

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Identity\Notifications\AccountPasswordLink;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->clinic = $this->createClinic('sorriso');
    $this->admin = $this->clinic->run(fn () => User::factory()->admin()->create(['password' => 'Sorriso123']));
    $this->professional = $this->clinic->run(fn () => User::factory()->create(['password' => 'Sorriso123']));
    $this->receptionist = $this->clinic->run(fn () => User::factory()->reception()->create(['password' => 'Sorriso123']));

    $this->signIn = fn (User $user) => $this->clinicRequest('sorriso', 'POST', 'login', [
        'email' => $user->email,
        'password' => 'Sorriso123',
    ])->assertOk();
});

function userPayload(array $overrides = []): array
{
    return [
        'name' => 'Nova Pessoa',
        'email' => 'nova@sorriso.app',
        'role' => 'professional',
        'specialty' => 'Endodontia',
        ...$overrides,
    ];
}

it('lets only the admin manage users', function (string $who) {
    ($this->signIn)($this->{$who});

    $this->clinicRequest('sorriso', 'GET', 'users')->assertForbidden();
    $this->clinicRequest('sorriso', 'GET', "users/{$this->admin->id}")->assertForbidden();
    $this->clinicRequest('sorriso', 'POST', 'users', userPayload())->assertForbidden();
    $this->clinicRequest('sorriso', 'PUT', "users/{$this->admin->id}", userPayload())->assertForbidden();
    $this->clinicRequest('sorriso', 'DELETE', "users/{$this->admin->id}")->assertForbidden();
    $this->clinicRequest('sorriso', 'GET', 'clinic')->assertForbidden();
    $this->clinicRequest('sorriso', 'PUT', 'clinic', ['name' => 'Outra', 'unit' => 'Outra'])->assertForbidden();
})->with(['professional', 'receptionist']);

it('requires authentication', function () {
    $this->clinicRequest('sorriso', 'GET', 'users')->assertUnauthorized();
});

it('lists the team without sensitive fields', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'GET', 'users')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonMissingPath('data.0.password')
        ->assertJsonMissingPath('data.0.remember_token');
});

it('creates a user and sends the link to set a password', function () {
    ($this->signIn)($this->admin);

    $response = $this->clinicRequest('sorriso', 'POST', 'users', userPayload(['email' => 'NOVA@sorriso.app']))
        ->assertCreated()
        ->assertJsonPath('email', 'nova@sorriso.app')
        ->assertJsonPath('roleLabel', 'Profissional')
        ->assertJsonPath('attendsPatients', true);

    $created = $this->clinic->run(fn () => User::query()->findOrFail($response->json('id')));

    Notification::assertSentTo($created, AccountPasswordLink::class, fn (AccountPasswordLink $notification) => $notification->isInvitation
        && str_starts_with($notification->url, 'http://sorriso.localhost:3000/redefinir-senha'));
});

it('derives who attends patients from the role', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'POST', 'users', userPayload(['role' => 'reception', 'attends_patients' => true]))
        ->assertCreated()
        ->assertJsonPath('attendsPatients', false);

    $this->clinicRequest('sorriso', 'POST', 'users', userPayload(['email' => 'outra@sorriso.app', 'attends_patients' => false]))
        ->assertCreated()
        ->assertJsonPath('attendsPatients', true);

    $this->clinicRequest('sorriso', 'POST', 'users', userPayload(['email' => 'admin2@sorriso.app', 'role' => 'admin', 'attends_patients' => true]))
        ->assertCreated()
        ->assertJsonPath('attendsPatients', true);
});

it('validates the user', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'POST', 'users', ['name' => '', 'email' => $this->professional->email, 'role' => 'dono'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'role'])
        ->assertJsonValidationErrors(['email' => 'Este e-mail já está em uso.']);
});

it('updates a user keeping its own e-mail valid', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'PUT', "users/{$this->professional->id}", userPayload([
        'name' => 'Nome Novo',
        'email' => $this->professional->email,
        'role' => 'reception',
    ]))
        ->assertOk()
        ->assertJsonPath('name', 'Nome Novo')
        ->assertJsonPath('role', 'reception')
        ->assertJsonPath('attendsPatients', false);
});

it('deactivates another user', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'PUT', "users/{$this->professional->id}", userPayload([
        'email' => $this->professional->email,
        'active' => false,
    ]))
        ->assertOk()
        ->assertJsonPath('active', false);
});

it('does not let an admin remove the own access', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'PUT', "users/{$this->admin->id}", userPayload(['email' => $this->admin->email, 'role' => 'professional']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user']);

    $this->clinicRequest('sorriso', 'PUT', "users/{$this->admin->id}", userPayload(['email' => $this->admin->email, 'role' => 'admin', 'active' => false]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user']);

    $this->clinicRequest('sorriso', 'DELETE', "users/{$this->admin->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user']);

    expect($this->clinic->run(fn () => $this->admin->fresh()->role))->toBe(UserRole::Admin);
});

it('lets an admin edit the own profile', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'PUT', "users/{$this->admin->id}", userPayload(['name' => 'Novo Nome', 'email' => $this->admin->email, 'role' => 'admin']))
        ->assertOk()
        ->assertJsonPath('name', 'Novo Nome');
});

it('deletes a user without appointments', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'DELETE', "users/{$this->receptionist->id}")->assertNoContent();

    expect($this->clinic->run(fn () => $this->receptionist->fresh()))->toBeNull();
});

it('keeps a user who has appointments', function () {
    $this->clinic->run(fn () => Appointment::factory()->create(['professional_id' => $this->professional->id]));

    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'DELETE', "users/{$this->professional->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['user' => 'Este usuário tem agendamentos. Desative a conta em vez de excluí-la.']);
});

it('answers not found for a user that does not exist in this clinic', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'GET', 'users/999')->assertNotFound();
});

it('lets the admin read and update the clinic', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'GET', 'clinic')->assertOk()->assertJsonPath('unit', 'Unidade principal');

    $this->clinicRequest('sorriso', 'PUT', 'clinic', [
        'name' => 'Nome Novo',
        'unit' => 'Unidade Centro',
        'cnpj' => '12.345.678/0001-90',
        'phone' => '(11) 3051-8090',
        'email' => 'contato@sorriso.app',
        'address' => 'Rua A, 10',
    ])
        ->assertOk()
        ->assertJsonPath('name', 'Nome Novo');

    expect($this->clinic->fresh()->name)->toBe('Nome Novo');

    $this->clinicRequest('sorriso', 'PUT', 'clinic', ['name' => '', 'unit' => 'U', 'cnpj' => '123'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'cnpj']);
});
