<?php

use App\Domains\Identity\Models\User;

beforeEach(function () {
    $this->clinic = $this->createClinic('sorriso');
    $this->clinic->run(fn () => User::factory()->admin()->create([
        'email' => 'admin@sorriso.app',
        'password' => 'Sorriso123',
    ]));
});

it('logs in and returns the session contract', function () {
    $this->clinicRequest('sorriso', 'POST', 'login', ['email' => ' ADMIN@sorriso.app ', 'password' => 'Sorriso123'])
        ->assertOk()
        ->assertJsonPath('user.email', 'admin@sorriso.app')
        ->assertJsonPath('user.role', 'admin')
        ->assertJsonPath('user.roleLabel', 'Administrador')
        ->assertJsonPath('tenant.slug', 'sorriso')
        ->assertJsonPath('plan.name', 'Pro')
        ->assertJsonPath('plan.trialDaysLeft', 14)
        ->assertJsonMissingPath('user.password');

    $this->clinicRequest('sorriso', 'GET', 'session')
        ->assertOk()
        ->assertJsonPath('user.email', 'admin@sorriso.app');
});

it('gives the same answer for a wrong password, an unknown e-mail and an inactive account', function () {
    $this->clinic->run(fn () => User::factory()->inactive()->create([
        'email' => 'inativo@sorriso.app',
        'password' => 'Sorriso123',
    ]));

    $attempts = [
        ['email' => 'admin@sorriso.app', 'password' => 'errada'],
        ['email' => 'ninguem@sorriso.app', 'password' => 'Sorriso123'],
        ['email' => 'inativo@sorriso.app', 'password' => 'Sorriso123'],
    ];

    foreach ($attempts as $attempt) {
        $this->clinicRequest('sorriso', 'POST', 'login', $attempt)
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'E-mail ou senha incorretos.']);
    }
});

it('validates the login fields', function () {
    $this->clinicRequest('sorriso', 'POST', 'login', ['email' => 'invalido', 'password' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});

it('limits login attempts', function () {
    foreach (range(1, 5) as $attempt) {
        $this->clinicRequest('sorriso', 'POST', 'login', ['email' => 'admin@sorriso.app', 'password' => 'errada'])
            ->assertUnprocessable();
    }

    $this->clinicRequest('sorriso', 'POST', 'login', ['email' => 'admin@sorriso.app', 'password' => 'Sorriso123'])
        ->assertTooManyRequests();
});

it('refuses the session without authentication', function () {
    $this->clinicRequest('sorriso', 'GET', 'session')->assertUnauthorized();
});

it('ends the session on logout', function () {
    $this->clinicRequest('sorriso', 'POST', 'login', ['email' => 'admin@sorriso.app', 'password' => 'Sorriso123'])->assertOk();

    $this->clinicRequest('sorriso', 'POST', 'logout')->assertNoContent();

    $this->clinicRequest('sorriso', 'GET', 'session')->assertUnauthorized();
});

it('cuts the access of a deactivated user on the next request', function () {
    $user = $this->clinic->run(fn () => User::factory()->create(['password' => 'Sorriso123']));

    $this->clinicRequest('sorriso', 'POST', 'login', ['email' => $user->email, 'password' => 'Sorriso123'])->assertOk();

    $this->clinic->run(fn () => $user->update(['active' => false]));

    $this->clinicRequest('sorriso', 'GET', 'session')->assertUnauthorized();
});
