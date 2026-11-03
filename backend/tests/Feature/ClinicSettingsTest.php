<?php

use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Plan;

beforeEach(function () {
    $this->clinic = $this->createClinic('sorriso');
    $this->admin = $this->clinic->run(fn () => User::factory()->admin()->create(['password' => 'Sorriso123']));
    $this->receptionist = $this->clinic->run(fn () => User::factory()->reception()->create(['password' => 'Sorriso123']));
});

describe('preferences', function () {
    it('starts with every preference on', function () {
        $this->signIn('sorriso', $this->admin);

        $this->clinicRequest('sorriso', 'GET', 'clinic/preferences')
            ->assertOk()
            ->assertExactJson(['autoConfirmation' => true, 'waitingList' => true, 'reminder24h' => true]);
    });

    it('saves the preferences of the clinic', function () {
        $this->signIn('sorriso', $this->admin);

        $this->clinicRequest('sorriso', 'PUT', 'clinic/preferences', [
            'auto_confirmation' => false,
            'waiting_list' => true,
            'reminder_24h' => false,
        ])
            ->assertOk()
            ->assertJsonPath('autoConfirmation', false)
            ->assertJsonPath('reminder24h', false);

        $clinic = $this->clinic->fresh();

        expect($clinic->preferences())->toBe(['auto_confirmation' => false, 'waiting_list' => true, 'reminder_24h' => false])
            ->and($clinic->sendsReminders())->toBeFalse()
            ->and($clinic->name)->toBe('Clínica sorriso');
    });

    it('refuses values that are not booleans', function () {
        $this->signIn('sorriso', $this->admin);

        $this->clinicRequest('sorriso', 'PUT', 'clinic/preferences', ['auto_confirmation' => 'talvez'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['auto_confirmation', 'waiting_list', 'reminder_24h']);
    });

    it('lets only the admin see and change them', function () {
        $this->signIn('sorriso', $this->receptionist);

        $this->clinicRequest('sorriso', 'GET', 'clinic/preferences')->assertForbidden();
        $this->clinicRequest('sorriso', 'PUT', 'clinic/preferences', [
            'auto_confirmation' => true,
            'waiting_list' => true,
            'reminder_24h' => false,
        ])->assertForbidden();

        expect($this->clinic->fresh()->sendsReminders())->toBeTrue();
    });
});

describe('subscription', function () {
    beforeEach(function () {
        Plan::query()->firstOrCreate(['code' => 'free'], ['name' => 'Free', 'price_cents' => 0, 'max_professionals' => 1]);
        Plan::query()->firstOrCreate(['code' => 'enterprise'], ['name' => 'Enterprise', 'price_cents' => 59900, 'max_professionals' => null]);
    });

    it('shows the current plan and the plans by price', function () {
        $this->signIn('sorriso', $this->admin);

        $this->clinicRequest('sorriso', 'GET', 'clinic/subscription')
            ->assertOk()
            ->assertJsonPath('status', 'trial')
            ->assertJsonPath('trialDaysLeft', 14)
            ->assertJsonPath('plan', ['code' => 'pro', 'name' => 'Pro', 'priceCents' => 24900, 'maxProfessionals' => 5])
            ->assertJsonPath('plans.*.code', ['free', 'pro', 'enterprise']);
    });

    it('tells an expired trial from an active plan', function (?string $trialEndsAt, string $status) {
        $this->clinic->update(['trial_ends_at' => $trialEndsAt ? now()->modify($trialEndsAt) : null]);
        $this->signIn('sorriso', $this->admin);

        $this->clinicRequest('sorriso', 'GET', 'clinic/subscription')->assertJsonPath('status', $status);
    })->with([
        'expired trial' => ['-1 day', 'expired'],
        'paid plan' => [null, 'active'],
    ]);

    it('is visible only to the admin', function () {
        $this->signIn('sorriso', $this->receptionist);

        $this->clinicRequest('sorriso', 'GET', 'clinic/subscription')->assertForbidden();
    });
});
