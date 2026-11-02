<?php

use App\Domains\Identity\Models\User;
use App\Domains\Identity\Notifications\AccountPasswordLink;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->clinic = $this->createClinic('sorriso');
    $this->user = $this->clinic->run(fn () => User::factory()->create(['email' => 'ana@sorriso.app']));
});

it('answers the same whether or not the e-mail has an account', function () {
    $this->clinicRequest('sorriso', 'POST', 'forgot-password', ['email' => 'ana@sorriso.app'])->assertNoContent();
    $this->clinicRequest('sorriso', 'POST', 'forgot-password', ['email' => 'ninguem@sorriso.app'])->assertNoContent();

    Notification::assertSentTimes(AccountPasswordLink::class, 1);
});

it('links to the clinic in the tenant record, not to the request host', function () {
    $this->clinicRequest('sorriso', 'POST', 'forgot-password', ['email' => 'ana@sorriso.app'])
        ->assertNoContent();

    Notification::assertSentTo($this->user, AccountPasswordLink::class, function (AccountPasswordLink $notification) {
        expect($notification->url)
            ->toStartWith('http://sorriso.localhost:3000/redefinir-senha?token=')
            ->toContain('email=ana%40sorriso.app');

        return true;
    });
});

it('resets the password with a valid token', function () {
    $this->clinicRequest('sorriso', 'POST', 'forgot-password', ['email' => 'ana@sorriso.app'])->assertNoContent();

    $token = null;
    Notification::assertSentTo($this->user, AccountPasswordLink::class, function (AccountPasswordLink $notification) use (&$token) {
        parse_str((string) parse_url($notification->url, PHP_URL_QUERY), $query);
        $token = $query['token'];

        return true;
    });

    $this->clinicRequest('sorriso', 'POST', 'reset-password', [
        'token' => $token,
        'email' => 'ana@sorriso.app',
        'password' => 'NovaSenha123',
        'password_confirmation' => 'NovaSenha123',
    ])->assertNoContent();

    expect(Hash::check('NovaSenha123', $this->clinic->run(fn () => $this->user->fresh()->password)))->toBeTrue();
});

it('reports an invalid token under the token field', function () {
    $this->clinicRequest('sorriso', 'POST', 'reset-password', [
        'token' => 'token-invalido',
        'email' => 'ana@sorriso.app',
        'password' => 'NovaSenha123',
        'password_confirmation' => 'NovaSenha123',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['token' => 'Este link é inválido ou expirou.']);
});

it('repeats the password rules of the frontend', function () {
    $this->clinicRequest('sorriso', 'POST', 'reset-password', [
        'token' => 'token',
        'email' => 'ana@sorriso.app',
        'password' => 'curta',
        'password_confirmation' => 'outra',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});
