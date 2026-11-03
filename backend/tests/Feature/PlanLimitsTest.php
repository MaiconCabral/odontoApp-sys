<?php

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Plan;
use Illuminate\Support\Facades\Notification;

// The trial plan of the tests allows 5 professionals; the admin below is the first one.
beforeEach(function () {
    Notification::fake();

    $this->clinic = $this->createClinic('sorriso');
    $this->admin = $this->clinic->run(fn () => User::factory()->admin()->create([
        'password' => 'Sorriso123',
        'attends_patients' => true,
    ]));

    $this->fill = fn (int $count) => $this->clinic->run(fn () => User::factory()->count($count)->create());

    $this->payload = fn (array $overrides = []) => [
        'name' => 'Nova Pessoa',
        'email' => 'nova@sorriso.app',
        'role' => 'professional',
        ...$overrides,
    ];

    $this->signIn('sorriso', $this->admin);
});

it('refuses one more professional when the plan is full', function () {
    ($this->fill)(4);

    $this->clinicRequest('sorriso', 'POST', 'users', ($this->payload)())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['role' => 'Seu plano permite até 5 profissionais. Fale com a gente para mudar de plano.']);

    expect($this->clinic->run(fn () => User::query()->count()))->toBe(5);
});

it('still adds reception and inactive users when the plan is full', function () {
    ($this->fill)(4);

    $this->clinicRequest('sorriso', 'POST', 'users', ($this->payload)(['role' => 'reception']))->assertCreated();
    $this->clinicRequest('sorriso', 'POST', 'users', ($this->payload)(['email' => 'inativa@sorriso.app', 'active' => false]))->assertCreated();
});

it('refuses activating a professional or making an admin attend past the limit', function () {
    ($this->fill)(4);
    [$inactive, $office] = $this->clinic->run(fn () => [
        User::factory()->inactive()->create(),
        User::factory()->admin()->create(['attends_patients' => false]),
    ]);

    $this->clinicRequest('sorriso', 'PUT', "users/{$inactive->id}", [
        'name' => $inactive->name, 'email' => $inactive->email, 'role' => 'professional', 'active' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors(['role']);

    $this->clinicRequest('sorriso', 'PUT', "users/{$office->id}", [
        'name' => $office->name, 'email' => $office->email, 'role' => 'admin', 'attends_patients' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors(['role']);
});

it('keeps who already counted after a downgrade', function () {
    ($this->fill)(4);
    $this->clinic->update(['plan_id' => Plan::query()->firstOrCreate(
        ['code' => 'free'],
        ['name' => 'Free', 'price_cents' => 0, 'max_professionals' => 1],
    )->id]);
    $ana = $this->clinic->run(fn () => User::query()->where('role', 'professional')->first());

    $this->clinicRequest('sorriso', 'PUT', "users/{$ana->id}", [
        'name' => 'Ana Renomeada', 'email' => $ana->email, 'role' => 'professional',
    ])->assertOk();

    $this->clinicRequest('sorriso', 'POST', 'users', ($this->payload)())
        ->assertJsonValidationErrors(['role' => 'Seu plano permite até 1 profissional. Fale com a gente para mudar de plano.']);
});

it('has no limit when the plan has none', function () {
    $this->clinic->update(['plan_id' => Plan::query()->firstOrCreate(
        ['code' => 'enterprise'],
        ['name' => 'Enterprise', 'price_cents' => 59900, 'max_professionals' => null],
    )->id]);
    ($this->fill)(10);

    $this->clinicRequest('sorriso', 'POST', 'users', ($this->payload)())->assertCreated();
});

it('tells the panel the limit and how many are used', function () {
    ($this->fill)(2);

    $this->clinicRequest('sorriso', 'GET', 'session')->assertJsonPath('plan.maxProfessionals', 5);
    $this->clinicRequest('sorriso', 'GET', 'clinic/subscription')->assertJsonPath('professionalsUsed', 3);
});
