<?php

use App\Domains\Identity\Models\User;

// Nuxt moves to the next port when 3000 is busy, so local origins cannot depend on it.
it('accepts the local frontend on any port', function (string $origin) {
    $this->createClinic('sorriso');

    $this->withHeaders(['Origin' => $origin, 'Access-Control-Request-Method' => 'POST'])
        ->options('http://sorriso.localhost/api/login')
        ->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', $origin)
        ->assertHeader('Access-Control-Allow-Credentials', 'true');
})->with([
    'http://sorriso.localhost:3000',
    'http://sorriso.localhost:3001',
    'http://localhost:3001',
]);

it('refuses origins outside the local domain', function (string $origin) {
    $this->createClinic('sorriso');

    $this->withHeaders(['Origin' => $origin, 'Access-Control-Request-Method' => 'POST'])
        ->options('http://sorriso.localhost/api/login')
        ->assertHeaderMissing('Access-Control-Allow-Origin');
})->with([
    'http://evil.example',
    'http://sorriso.localhost.evil.example:3000',
    'https://evillocalhost:3000',
]);

it('keeps the session for a frontend on another local port', function () {
    $clinic = $this->createClinic('sorriso');
    $clinic->run(fn () => User::factory()->admin()->create(['email' => 'admin@sorriso.app', 'password' => 'Sorriso123']));

    $this->resetRequestState();
    $this->withHeaders(['Origin' => 'http://sorriso.localhost:3001', 'Accept' => 'application/json'])
        ->postJson('http://sorriso.localhost/api/login', ['email' => 'admin@sorriso.app', 'password' => 'Sorriso123'])
        ->assertOk();

    $this->resetRequestState();
    $this->withHeaders(['Origin' => 'http://sorriso.localhost:3001', 'Accept' => 'application/json'])
        ->getJson('http://sorriso.localhost/api/session')
        ->assertOk()
        ->assertJsonPath('user.email', 'admin@sorriso.app');
});
