<?php

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Finance\Models\Payment;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

// Tuesday, 10 November 2026. A completed R$ 150 appointment of Ana this morning.
beforeEach(function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-11-10 15:00'));

    $this->clinic = $this->createClinic('sorriso');
    $this->evaluation = $this->procedureId($this->clinic, 'Avaliação');
    $this->cleaning = $this->procedureId($this->clinic, 'Limpeza e prevenção');

    $this->clinic->run(function () {
        $this->admin = User::factory()->admin()->create(['password' => 'Sorriso123']);
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['name' => 'Ana', 'password' => 'Sorriso123']);
        $this->felipe = User::factory()->create(['name' => 'Felipe']);
        $this->visit = Appointment::factory()->withStatus(AppointmentStatus::Completed)->at('2026-11-10 09:00')->create([
            'professional_id' => $this->ana->id,
            'procedure_id' => $this->evaluation,
            'price_cents' => 15000,
        ]);
    });

    $this->pay = fn (array $data, ?Appointment $appointment = null) => $this->clinicRequest(
        'sorriso',
        'POST',
        'appointments/'.($appointment ?? $this->visit)->id.'/payments',
        ['method' => 'pix', ...$data],
    );
});

describe('payments', function () {
    it('records partial payments up to the price and shows the balance', function () {
        $this->signIn('sorriso', $this->receptionist);

        ($this->pay)(['amount_cents' => 5000])->assertCreated()
            ->assertJsonPath('paidCents', 5000)
            ->assertJsonPath('balanceCents', 10000)
            ->assertJsonPath('paymentStatus', 'partial');

        ($this->pay)(['amount_cents' => 11000])->assertUnprocessable()
            ->assertJsonValidationErrors(['amount_cents' => 'O valor passa do preço da consulta. Faltam R$ 100,00.']);

        ($this->pay)(['amount_cents' => 10000, 'method' => 'credit_card'])->assertCreated()
            ->assertJsonPath('paymentStatus', 'paid')
            ->assertJsonCount(2, 'payments');

        $this->clinicRequest('sorriso', 'GET', "appointments/{$this->visit->id}")
            ->assertJsonPath('paidCents', 15000)
            ->assertJsonPath('paymentStatus', 'paid');
    });

    it('accepts more than the price only when asked', function () {
        $this->signIn('sorriso', $this->receptionist);

        ($this->pay)(['amount_cents' => 20000, 'allow_over' => true])->assertCreated()->assertJsonPath('balanceCents', 0);
    });

    it('refuses requests, cancelled appointments, future dates and empty amounts', function () {
        $cancelled = $this->clinic->run(fn () => Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->at('2026-11-10 10:00')->create());
        $this->signIn('sorriso', $this->receptionist);

        ($this->pay)(['amount_cents' => 1000], $cancelled)->assertJsonValidationErrors(['appointment']);
        ($this->pay)(['amount_cents' => 0, 'paid_at' => '2026-11-11'])->assertJsonValidationErrors(['amount_cents', 'paid_at']);
    });

    it('lets only the admin void, with a reason, and the void stops counting', function () {
        $this->signIn('sorriso', $this->receptionist);
        $id = ($this->pay)(['amount_cents' => 15000])->json('payments.0.id');

        $this->clinicRequest('sorriso', 'POST', "payments/{$id}/void", ['reason' => 'Lançado em dobro'])->assertForbidden();

        $this->signIn('sorriso', $this->admin);
        $this->clinicRequest('sorriso', 'POST', "payments/{$id}/void", [])->assertJsonValidationErrors(['reason']);
        $this->clinicRequest('sorriso', 'POST', "payments/{$id}/void", ['reason' => 'Lançado em dobro'])
            ->assertOk()
            ->assertJsonPath('paidCents', 0)
            ->assertJsonPath('payments.0.voided', true)
            ->assertJsonPath('payments.0.voidReason', 'Lançado em dobro');
        $this->clinicRequest('sorriso', 'POST', "payments/{$id}/void", ['reason' => 'De novo'])->assertJsonValidationErrors(['payment']);

        expect($this->clinic->run(fn () => Payment::query()->count()))->toBe(1);
    });

    it('is closed to professionals', function () {
        $this->signIn('sorriso', $this->ana);

        $this->clinicRequest('sorriso', 'GET', "appointments/{$this->visit->id}/payments")->assertForbidden();
        ($this->pay)(['amount_cents' => 1000])->assertForbidden();
    });

    it('shows what was received today on the overview, with the estimate', function () {
        $this->signIn('sorriso', $this->receptionist);
        ($this->pay)(['amount_cents' => 5000]);

        $revenue = collect($this->clinicRequest('sorriso', 'GET', 'overview')->json('metrics'))->firstWhere('id', 'revenue');

        expect($revenue['label'])->toBe('Recebido hoje')
            ->and($revenue['value'])->toBe('R$ 50')
            ->and($revenue['detail'])->toBe('Previsto R$ 150');
    });

    it('marks the payment status on the agenda', function () {
        $this->signIn('sorriso', $this->receptionist);
        ($this->pay)(['amount_cents' => 5000]);

        $this->clinicRequest('sorriso', 'GET', 'agenda?from=2026-11-09&to=2026-11-15')
            ->assertJsonPath('events.0.paymentStatus', 'partial');
    });
});

describe('reports', function () {
    beforeEach(function () {
        $this->clinic->run(function () {
            Appointment::factory()->withStatus(AppointmentStatus::NoShow)->at('2026-11-03 09:00')->create([
                'professional_id' => $this->ana->id, 'procedure_id' => $this->evaluation, 'price_cents' => 15000,
            ]);
            Appointment::factory()->withStatus(AppointmentStatus::Completed)->at('2026-11-04 14:00')->create([
                'professional_id' => $this->felipe->id, 'procedure_id' => $this->cleaning, 'price_cents' => 30000,
            ]);
            Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->at('2026-11-05 14:00')->create([
                'professional_id' => $this->felipe->id, 'price_cents' => 99900,
            ]);
        });

        $this->signIn('sorriso', $this->receptionist);
        ($this->pay)(['amount_cents' => 15000]);
        ($this->pay)(['amount_cents' => 5000, 'method' => 'cash', 'paid_at' => '2026-11-09', 'allow_over' => true]);
        $this->signIn('sorriso', $this->admin);
    });

    it('groups by professional with expected and received money', function () {
        $response = $this->clinicRequest('sorriso', 'GET', 'reports?from=2026-11-01&to=2026-11-30&group=professional')->assertOk();

        expect($response->json('rows'))->toBe([
            ['key' => (string) $this->felipe->id, 'label' => 'Felipe', 'appointments' => 1, 'completed' => 1, 'noShow' => 0, 'absenceRate' => 0, 'expectedCents' => 30000, 'receivedCents' => 0],
            ['key' => (string) $this->ana->id, 'label' => 'Ana', 'appointments' => 2, 'completed' => 1, 'noShow' => 1, 'absenceRate' => 50, 'expectedCents' => 15000, 'receivedCents' => 20000],
        ])->and($response->json('totals'))->toMatchArray(['appointments' => 3, 'expectedCents' => 45000, 'receivedCents' => 20000, 'absenceRate' => 33.3])
            ->and($response->json('byMethod'))->toBe([
                ['method' => 'pix', 'methodLabel' => 'Pix', 'receivedCents' => 15000],
                ['method' => 'cash', 'methodLabel' => 'Dinheiro', 'receivedCents' => 5000],
            ]);
    });

    it('groups by day using the payment date for the money received', function () {
        $rows = collect($this->clinicRequest('sorriso', 'GET', 'reports?from=2026-11-01&to=2026-11-30&group=day')->json('rows'))->keyBy('key');

        expect($rows->keys()->all())->toBe(['2026-11-03', '2026-11-04', '2026-11-09', '2026-11-10'])
            ->and($rows['2026-11-09']['receivedCents'])->toBe(5000)
            ->and($rows['2026-11-09']['appointments'])->toBe(0)
            ->and($rows['2026-11-10']['label'])->toBe('10/11/2026');
    });

    it('groups by procedure', function () {
        $labels = collect($this->clinicRequest('sorriso', 'GET', 'reports?from=2026-11-01&to=2026-11-30&group=procedure')->json('rows'))->pluck('label')->all();

        expect($labels)->toBe(['Limpeza e prevenção', 'Avaliação']);
    });

    it('limits the period and the access', function () {
        $this->clinicRequest('sorriso', 'GET', 'reports?from=2026-01-01&to=2027-06-01&group=day')
            ->assertUnprocessable()->assertJsonValidationErrors(['to']);
        $this->clinicRequest('sorriso', 'GET', 'reports?from=2026-11-01&to=2026-11-30&group=room')
            ->assertJsonValidationErrors(['group']);

        $this->signIn('sorriso', $this->receptionist);
        $this->clinicRequest('sorriso', 'GET', 'reports?from=2026-11-01&to=2026-11-30&group=day')->assertForbidden();
    });
});
