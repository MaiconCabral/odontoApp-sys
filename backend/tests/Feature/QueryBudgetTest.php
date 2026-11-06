<?php

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\WaitlistEntry;
use App\Domains\Clinical\Models\ClinicalNote;
use App\Domains\Clinical\Models\PatientAttachment;
use App\Domains\Finance\Models\Payment;
use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Enums\StaffMessage;
use App\Domains\Notifications\Notifications\StaffAlert;
use Carbon\CarbonImmutable;

// Every list and detail endpoint must run the same number of queries for one row and for many.
// A higher count for more rows means a query per row (N+1).

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-10 15:00'));

    $this->clinic = $this->createClinic('sorriso');
    $this->evaluation = $this->procedureId($this->clinic, 'Avaliação');

    $this->clinic->run(function () {
        $this->admin = User::factory()->admin()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['name' => 'Ana', 'password' => 'Sorriso123']);
        $this->patient = Patient::factory()->create(['name' => 'Camila Ferreira']);
        $this->visit = Appointment::factory()->withStatus(AppointmentStatus::Completed)->at('2026-11-10 09:00')->create([
            'professional_id' => $this->ana->id,
            'patient_id' => $this->patient->id,
            'procedure_id' => $this->evaluation,
            'price_cents' => 50000,
        ]);
    });

    $this->signIn('sorriso', $this->admin);

    // Queries of a GET, with the rows the seed callback adds in between.
    $this->budget = function (string $uri, callable $seed): array {
        $this->clinic->run(fn () => $seed(1));
        // Warms the caches, so both counts below come from the same state.
        $this->clinicRequest('sorriso', 'GET', $uri)->assertOk();
        $few = $this->countQueries(fn () => $this->clinicRequest('sorriso', 'GET', $uri)->assertOk());

        $this->clinic->run(fn () => $seed(6));
        $many = $this->countQueries(fn () => $this->clinicRequest('sorriso', 'GET', $uri)->assertOk());

        return [$few, $many];
    };

    $this->pay = fn (int $times) => collect(range(1, $times))->each(fn () => Payment::create([
        'appointment_id' => $this->visit->id,
        'patient_id' => $this->patient->id,
        'amount_cents' => 1000,
        'method' => 'pix',
        'paid_at' => '2026-11-10',
        'recorded_by' => $this->admin->id,
    ]));
});

it('keeps the appointment requests list flat', function () {
    [$few, $many] = ($this->budget)('appointment-requests', fn (int $n) => Appointment::factory()->count($n)->requested()
        ->at('2026-11-20 09:00')->create(['professional_id' => $this->ana->id]));

    expect($many)->toBe($few);
});

it('reads the paid sum once on the appointment detail', function () {
    [$few, $many] = ($this->budget)("appointments/{$this->visit->id}", $this->pay);

    expect($many)->toBe($few);

    $sums = collect($this->recordQueries(fn () => $this->clinicRequest('sorriso', 'GET', "appointments/{$this->visit->id}")->assertOk()))
        ->filter(fn (string $sql) => str_contains(strtolower($sql), 'sum('));
    expect($sums)->toHaveCount(1);
});

it('keeps the payment summary flat and sums without another query', function () {
    [$few, $many] = ($this->budget)("appointments/{$this->visit->id}/payments", $this->pay);

    expect($many)->toBe($few);

    $sums = collect($this->recordQueries(fn () => $this->clinicRequest('sorriso', 'GET', "appointments/{$this->visit->id}/payments")->assertOk()))
        ->filter(fn (string $sql) => str_contains(strtolower($sql), 'sum('));
    expect($sums)->toBeEmpty();

    $summary = $this->clinicRequest('sorriso', 'GET', "appointments/{$this->visit->id}/payments")->json();
    expect($summary['paidCents'])->toBe(7000)->and($summary['balanceCents'])->toBe(43000);
});

it('keeps the patient list flat', function () {
    [$few, $many] = ($this->budget)('patients', fn (int $n) => Patient::factory()->count($n)->create());

    expect($many)->toBe($few);
});

it('keeps the waitlist flat', function () {
    [$few, $many] = ($this->budget)('waitlist', fn (int $n) => collect(range(1, $n))->each(fn () => WaitlistEntry::create([
        'patient_id' => Patient::factory()->create()->id,
        'procedure_id' => $this->evaluation,
        'professional_id' => $this->ana->id,
        'from_date' => '2026-11-11',
        'to_date' => '2026-11-20',
        'period' => 'any',
        'status' => 'waiting',
    ])));

    expect($many)->toBe($few);
});

it('keeps the medical record notes flat', function () {
    [$few, $many] = ($this->budget)("patients/{$this->patient->id}/notes", fn (int $n) => collect(range(1, $n))->each(fn () => ClinicalNote::create([
        'patient_id' => $this->patient->id,
        'appointment_id' => $this->visit->id,
        'author_id' => $this->ana->id,
        'body' => 'Paciente sem queixas.',
    ])));

    expect($many)->toBe($few);
});

it('keeps the attachment list flat', function () {
    [$few, $many] = ($this->budget)("patients/{$this->patient->id}/attachments", fn (int $n) => collect(range(1, $n))->each(fn () => PatientAttachment::create([
        'patient_id' => $this->patient->id,
        'uploader_id' => $this->admin->id,
        'original_name' => 'raio-x.pdf',
        'path' => 'attachments/raio-x.pdf',
        'mime' => 'application/pdf',
        'size_bytes' => 1024,
    ])));

    expect($many)->toBe($few);
});

it('keeps the bell flat', function () {
    [$few, $many] = ($this->budget)('notifications', fn (int $n) => collect(range(1, $n))->each(fn () => $this->admin->notify(new StaffAlert(
        StaffMessage::BookingRequested,
        ['appointmentId' => (string) $this->visit->id, 'date' => '2026-11-20', 'title' => 'Nova solicitação', 'body' => 'Camila pediu um horário.'],
    ))));

    expect($many)->toBe($few);
});

it('keeps every report grouping flat', function (string $group) {
    [$few, $many] = ($this->budget)("reports?from=2026-11-01&to=2026-11-30&group={$group}", fn (int $n) => Appointment::factory()->count($n)
        ->withStatus(AppointmentStatus::Completed)->at('2026-11-05 09:00')
        ->create(['professional_id' => $this->ana->id, 'procedure_id' => $this->evaluation]));

    expect($many)->toBe($few);
})->with(['day', 'professional', 'procedure']);

it('keeps the public booking page flat and reads the procedures from the cache', function () {
    $this->resetRequestState();
    $first = $this->countQueries(fn () => $this->clinicRequest('sorriso', 'GET', 'public/booking')->assertOk());
    $second = $this->countQueries(fn () => $this->clinicRequest('sorriso', 'GET', 'public/booking')->assertOk());

    expect($second)->toBeLessThan($first);
});
