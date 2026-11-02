<?php

use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;

function weekWith(array $changes = []): array
{
    $days = [];

    foreach (range(1, 7) as $weekday) {
        $days[] = [
            'weekday' => $weekday,
            'closed' => $weekday === 7,
            'opens_at' => $weekday === 7 ? null : '08:00',
            'closes_at' => $weekday === 7 ? null : '18:00',
            'break_starts_at' => null,
            'break_ends_at' => null,
            ...($changes[$weekday] ?? []),
        ];
    }

    return ['days' => $days];
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-09 07:00'));

    $this->clinic = $this->createClinic('sorriso');

    $this->clinic->run(function () {
        $this->admin = User::factory()->admin()->create(['password' => 'Sorriso123']);
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['password' => 'Sorriso123']);
    });
});

it('starts every clinic with the default opening hours', function () {
    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'GET', 'business-hours')
        ->assertOk()
        ->assertJsonCount(7, 'data')
        ->assertJsonPath('data.0.label', 'Segunda')
        ->assertJsonPath('data.0.opensAt', '08:00')
        ->assertJsonPath('data.0.breakStartsAt', '12:00')
        ->assertJsonPath('data.5.closesAt', '12:00')
        ->assertJsonPath('data.6.closed', true);
});

it('lets only admins change the opening hours', function () {
    $this->signIn('sorriso', $this->receptionist);
    $this->clinicRequest('sorriso', 'PUT', 'business-hours', weekWith())->assertForbidden();

    $this->signIn('sorriso', $this->admin);
    $this->clinicRequest('sorriso', 'PUT', 'business-hours', weekWith([1 => ['opens_at' => '07:00', 'break_starts_at' => '12:00', 'break_ends_at' => '13:30']]))
        ->assertOk()
        ->assertJsonPath('data.0.opensAt', '07:00')
        ->assertJsonPath('data.0.breakEndsAt', '13:30')
        ->assertJsonPath('data.1.breakStartsAt', null);
});

it('validates the order of the times', function () {
    $this->signIn('sorriso', $this->admin);

    $this->clinicRequest('sorriso', 'PUT', 'business-hours', weekWith([
        1 => ['opens_at' => '18:00', 'closes_at' => '08:00'],
        2 => ['break_starts_at' => '07:00', 'break_ends_at' => '09:00'],
        3 => ['opens_at' => null],
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'days.0.closes_at' => 'O fechamento precisa ser depois da abertura.',
            'days.1.break_ends_at' => 'O intervalo precisa ficar dentro do expediente.',
            'days.2.opens_at' => 'Informe a abertura.',
        ]);
});

it('manages holidays and days off', function () {
    $this->signIn('sorriso', $this->receptionist);

    $block = $this->clinicRequest('sorriso', 'POST', 'schedule-blocks', [
        'professional_id' => $this->ana->id,
        'starts_at' => '2026-11-20 00:00',
        'ends_at' => '2026-11-20 23:59',
        'reason' => 'Folga',
    ])
        ->assertCreated()
        ->assertJsonPath('professionalName', $this->ana->name)
        ->json();

    $this->clinicRequest('sorriso', 'GET', 'schedule-blocks')->assertJsonCount(1, 'data');

    $this->clinicRequest('sorriso', 'PUT', "schedule-blocks/{$block['id']}", [
        'starts_at' => '2026-11-21 00:00',
        'ends_at' => '2026-11-21 12:00',
        'reason' => 'Feriado',
    ])
        ->assertOk()
        ->assertJsonPath('professionalId', null)
        ->assertJsonPath('endsAt', '2026-11-21 12:00');

    $this->clinicRequest('sorriso', 'POST', 'schedule-blocks', ['starts_at' => '2026-11-22 10:00', 'ends_at' => '2026-11-22 09:00', 'reason' => ''])
        ->assertJsonValidationErrors(['ends_at', 'reason']);

    $this->clinicRequest('sorriso', 'DELETE', "schedule-blocks/{$block['id']}")->assertNoContent();
});

it('keeps blocks away from professionals', function () {
    $this->signIn('sorriso', $this->ana);

    $this->clinicRequest('sorriso', 'GET', 'schedule-blocks')->assertForbidden();
    $this->clinicRequest('sorriso', 'POST', 'schedule-blocks', ['starts_at' => '2026-11-20 00:00', 'ends_at' => '2026-11-20 23:59', 'reason' => 'Folga'])
        ->assertForbidden();
});
