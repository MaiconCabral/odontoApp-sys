<?php

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\ScheduleBlock;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

// Thursday, 12 November 2026, 10:15.
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-12 10:15'));

    $this->clinic = $this->createClinic('sorriso');

    $this->clinic->run(function () {
        $this->admin = User::factory()->admin()->create(['name' => 'Dr. Carlos Mendes', 'password' => 'Sorriso123', 'attends_patients' => false]);
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['name' => 'Dra. Ana', 'password' => 'Sorriso123']);
        $this->patient = Patient::factory()->create(['name' => 'Camila Ferreira']);

        $make = fn (string $start, int $price, AppointmentStatus $status = AppointmentStatus::Scheduled, int $minutes = 60) => Appointment::factory()
            ->withStatus($status)
            ->at($start, $minutes)
            ->create(['professional_id' => $this->ana->id, 'patient_id' => $this->patient->id, 'price_cents' => $price]);

        // Today: done, ongoing, upcoming, missed and a request that does not count.
        $make('2026-11-12 08:00', 20000, AppointmentStatus::Completed);
        $make('2026-11-12 10:00', 45000, AppointmentStatus::Confirmed);
        $make('2026-11-12 14:00', 15000);
        $make('2026-11-12 16:00', 15000, AppointmentStatus::NoShow);
        Appointment::factory()->requested()->at('2026-11-12 17:00')->create(['professional_id' => $this->ana->id]);

        // Yesterday: two held appointments.
        $make('2026-11-11 09:00', 20000, AppointmentStatus::Completed);
        $make('2026-11-11 10:00', 20000, AppointmentStatus::Completed);

        // Previous week: four.
        foreach (['2026-11-02', '2026-11-03', '2026-11-04', '2026-11-05'] as $day) {
            $make("{$day} 09:00", 10000, AppointmentStatus::Completed);
        }

        // Last month: attendance 50%.
        $make('2026-10-20 09:00', 10000, AppointmentStatus::Completed);
        $make('2026-10-21 09:00', 10000, AppointmentStatus::NoShow);
    });
});

it('greets the user and counts today against yesterday', function () {
    $this->signIn('sorriso', $this->admin);

    $overview = $this->clinicRequest('sorriso', 'GET', 'overview')->assertOk()->json();
    $metrics = collect($overview['metrics'])->keyBy('id');

    expect($overview['greeting'])->toBe('Bom dia, Carlos')
        ->and($overview['agendaDate'])->toBe('12 de novembro de 2026')
        ->and($metrics['appointments'])->toMatchArray(['value' => '3', 'change' => '50,0%', 'trend' => 'up'])
        // Nothing was paid yet; the estimate by price stays in the detail line.
        ->and($metrics['revenue'])->toMatchArray(['label' => 'Recebido hoje', 'value' => 'R$ 0', 'detail' => 'Previsto R$ 800'])
        // This month: 7 completed and 1 missed. Last month: 1 and 1.
        ->and($metrics['attendance']['value'])->toBe('87,5%')
        ->and($metrics['attendance']['change'])->toBe('37,5 p.p.');
});

it('compares the week with the previous one', function () {
    $this->signIn('sorriso', $this->receptionist);

    $weekly = $this->clinicRequest('sorriso', 'GET', 'overview')->json('weeklyAppointments');

    expect($weekly['total'])->toBe(5)
        ->and($weekly['change'])->toBe('25,0%')
        ->and(collect($weekly['bars'])->pluck('value')->all())->toBe([0, 0, 2, 3, 0, 0, 0])
        ->and(collect($weekly['bars'])->firstWhere('highlighted', true)['day'])->toBe('Qui')
        ->and($weekly['axis'])->toBe([20, 15, 10, 5, 0]);
});

it('measures the occupancy without lunch and blocked time', function () {
    $this->signIn('sorriso', $this->admin);

    // Ana works 9 hours (08-12, 13-18): 4 held appointments of one hour make 44%.
    expect($this->clinicRequest('sorriso', 'GET', 'overview')->json('occupancyPercentage'))->toBe(44);

    $this->clinic->run(fn () => ScheduleBlock::create([
        'professional_id' => $this->ana->id, 'starts_at' => '2026-11-12 13:00', 'ends_at' => '2026-11-12 18:00', 'reason' => 'Curso',
    ]));

    expect($this->clinicRequest('sorriso', 'GET', 'overview')->json('occupancyPercentage'))->toBe(100);
});

it('lists today with the ongoing and upcoming first', function () {
    $this->signIn('sorriso', $this->admin);

    $rows = collect($this->clinicRequest('sorriso', 'GET', 'overview')->json('upcomingAppointments'));

    expect($rows->pluck('time')->all())->toBe(['10:00', '14:00', '08:00', '16:00'])
        ->and($rows->pluck('statusLabel')->all())->toBe(['Em atendimento', 'Aguardando', 'Concluído', 'Faltou'])
        ->and($rows->first())->toMatchArray(['patientInitials' => 'CF', 'professional' => 'Dra. Ana', 'duration' => '60 min']);
});

it('keeps the numbers away from professionals', function () {
    $this->signIn('sorriso', $this->ana);

    $this->clinicRequest('sorriso', 'GET', 'overview')->assertForbidden();
});

it('does not run a query per appointment', function () {
    $this->signIn('sorriso', $this->admin);

    $countQueries = function () {
        $this->resetRequestState();
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });
        $this->clinicRequest('sorriso', 'GET', 'overview')->assertOk();

        return $queries;
    };

    $few = $countQueries();
    $this->clinic->run(fn () => Appointment::factory()->count(8)->at('2026-11-12 15:00')->create(['professional_id' => $this->ana->id]));
    $many = $countQueries();

    expect($many)->toBe($few);
});
