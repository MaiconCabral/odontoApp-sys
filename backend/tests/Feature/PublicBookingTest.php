<?php

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;

// Monday, 9 November 2026. Patients may book from tomorrow on.
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-09 10:00'));

    $this->clinic = $this->createClinic('sorriso');

    $this->clinic->run(function () {
        $this->ana = User::factory()->create(['name' => 'Ana', 'specialty' => 'Ortodontia']);
        $this->felipe = User::factory()->create(['name' => 'Felipe']);
        User::factory()->reception()->create(['name' => 'Recepção']);
    });

    $this->evaluation = $this->procedureId($this->clinic, 'Avaliação');

    $this->form = fn (array $overrides = []) => [
        'name' => 'Paciente Novo',
        'phone' => '(11) 99999-0000',
        'email' => 'novo@paciente.app',
        'procedure_id' => $this->evaluation,
        'professional_id' => $this->ana->id,
        'date' => '2026-11-10',
        'time' => '09:00',
        'consent' => true,
        'website' => '',
        ...$overrides,
    ];
});

it('shows the clinic, the procedures and only who attends patients', function () {
    $this->clinicRequest('sorriso', 'GET', 'public/booking')
        ->assertOk()
        ->assertJsonPath('clinic.name', 'Clínica sorriso')
        ->assertJsonCount(5, 'procedures')
        ->assertJsonCount(2, 'professionals')
        ->assertJsonPath('professionals.0.name', 'Ana')
        ->assertJsonMissingPath('professionals.0.email');
});

it('lists free times of a day without the lunch break', function () {
    $response = $this->clinicRequest('sorriso', 'GET', "public/availability?date=2026-11-10&procedure_id={$this->evaluation}")->assertOk();

    expect($response->json('slots'))
        ->toContain('08:00', '11:30', '13:00', '17:30')
        ->not->toContain('12:00', '12:30', '18:00');
});

it('refuses days outside the booking window', function (string $date) {
    $this->clinicRequest('sorriso', 'GET', "public/availability?date={$date}&procedure_id={$this->evaluation}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);
})->with(['2026-11-09', '2027-02-01']);

it('saves a request that holds the time until the clinic answers', function () {
    $this->clinicRequest('sorriso', 'POST', 'public/booking-requests', ($this->form)())
        ->assertCreated()
        ->assertJsonPath('message', 'Recebemos seu pedido. A clínica vai entrar em contato para confirmar o horário.');

    $request = $this->clinic->run(fn () => Appointment::query()->with('patient')->sole());

    expect($request->status)->toBe(AppointmentStatus::Requested)
        ->and($request->source)->toBe(Appointment::SOURCE_PUBLIC)
        ->and($request->room_id)->toBeNull()
        ->and($request->patient->phone_digits)->toBe('11999990000');

    $slots = $this->clinicRequest('sorriso', 'GET', "public/availability?date=2026-11-10&procedure_id={$this->evaluation}&professional_id={$this->ana->id}")->json('slots');

    expect($slots)->not->toContain('09:00');

    $this->clinicRequest('sorriso', 'POST', 'public/booking-requests', ($this->form)(['phone' => '(11) 98888-0000']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['professional_id' => 'Este profissional já tem consulta neste horário.']);
});

it('reuses the patient found by phone or e-mail', function () {
    $this->clinic->run(fn () => Patient::factory()->create(['name' => 'Cadastrado', 'phone' => '11 99999-0000']));

    $this->clinicRequest('sorriso', 'POST', 'public/booking-requests', ($this->form)(['email' => null]))->assertCreated();

    expect($this->clinic->run(fn () => Patient::query()->count()))->toBe(1);
});

it('picks a free professional when the patient has no preference', function () {
    $this->clinic->run(fn () => Appointment::factory()->at('2026-11-10 09:00')->create(['professional_id' => $this->ana->id]));

    $this->clinicRequest('sorriso', 'POST', 'public/booking-requests', ($this->form)(['professional_id' => null]))->assertCreated();

    $request = $this->clinic->run(fn () => Appointment::query()->where('status', AppointmentStatus::Requested)->sole());

    expect($request->professional_id)->toBe($this->felipe->id);
});

it('answers a bot like a person, without saving anything', function () {
    $this->clinicRequest('sorriso', 'POST', 'public/booking-requests', ($this->form)(['website' => 'http://spam.example']))
        ->assertCreated();

    expect($this->clinic->run(fn () => Appointment::query()->count()))->toBe(0);
});

it('validates the patient data and the consent', function () {
    $this->clinicRequest('sorriso', 'POST', 'public/booking-requests', ($this->form)(['name' => 'A', 'phone' => '123', 'consent' => false, 'date' => '2026-11-09']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name' => 'Informe seu nome completo.',
            'phone' => 'Informe um telefone com DDD.',
            'consent' => 'Autorize o contato da clínica para continuar.',
            'date' => 'Escolha uma data a partir de amanhã.',
        ]);
});

it('limits the requests of one visitor', function () {
    foreach (['08:00', '08:30', '09:00', '09:30', '10:00'] as $time) {
        $this->clinicRequest('sorriso', 'POST', 'public/booking-requests', ($this->form)(['time' => $time]))->assertCreated();
    }

    $this->clinicRequest('sorriso', 'POST', 'public/booking-requests', ($this->form)(['time' => '10:30']))
        ->assertTooManyRequests();
});

it('does not need a session', function () {
    $this->resetRequestState();

    $this->getJson('http://sorriso.localhost/api/public/booking')->assertOk();
});
