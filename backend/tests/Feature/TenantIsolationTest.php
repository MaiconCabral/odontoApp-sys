<?php

use App\Domains\Identity\Models\User;

beforeEach(function () {
    $this->clinicA = $this->createClinic('clinica-a');
    $this->clinicB = $this->createClinic('clinica-b');

    // Both get id 1 in their own database, which is the case a forged session would exploit.
    $this->adminA = $this->clinicA->run(fn () => User::factory()->admin()->create(['email' => 'a@clinica.app', 'password' => 'Sorriso123']));
    $this->adminB = $this->clinicB->run(fn () => User::factory()->admin()->create(['email' => 'b@clinica.app', 'password' => 'Sorriso123']));
});

it('gives each clinic its own database', function () {
    expect($this->adminA->id)->toBe($this->adminB->id);

    expect($this->clinicA->run(fn () => User::query()->pluck('email')->all()))->toBe(['a@clinica.app']);
    expect($this->clinicB->run(fn () => User::query()->pluck('email')->all()))->toBe(['b@clinica.app']);
});

it('refuses a session from one clinic on another', function () {
    $this->clinicRequest('clinica-a', 'POST', 'login', ['email' => 'a@clinica.app', 'password' => 'Sorriso123'])->assertOk();

    $this->clinicRequest('clinica-b', 'GET', 'session')->assertForbidden();
    $this->clinicRequest('clinica-b', 'GET', 'users')->assertForbidden();
});

it('does not accept the credentials of another clinic', function () {
    $this->clinicRequest('clinica-b', 'POST', 'login', ['email' => 'a@clinica.app', 'password' => 'Sorriso123'])
        ->assertUnprocessable();
});

it('allows the same e-mail in two clinics', function () {
    $this->clinicB->run(fn () => User::factory()->create(['email' => 'a@clinica.app']));

    expect($this->clinicB->run(fn () => User::query()->where('email', 'a@clinica.app')->count()))->toBe(1);
    expect($this->clinicA->run(fn () => User::query()->where('email', 'a@clinica.app')->count()))->toBe(1);
});

it('keeps tenant routes off the central domain', function () {
    $this->centralRequest('POST', 'login', ['email' => 'a@clinica.app', 'password' => 'Sorriso123'])->assertNotFound();
    $this->centralRequest('GET', 'session')->assertNotFound();
});

it('answers not found for an unknown clinic', function () {
    $this->clinicRequest('nao-existe', 'POST', 'login', ['email' => 'a@clinica.app', 'password' => 'Sorriso123'])
        ->assertNotFound()
        ->assertExactJson(['message' => 'Clínica não encontrada.']);
});

it('refuses requests that do not come from the SPA', function () {
    $this->resetRequestState();

    $this->getJson('http://clinica-a.localhost/api/session')->assertUnauthorized();
});
