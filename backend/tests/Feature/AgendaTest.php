<?php

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Identity\Models\User;

beforeEach(function () {
    $this->clinic = $this->createClinic('sorriso');

    $this->clinic->run(function () {
        $this->admin = User::factory()->admin()->create(['password' => 'Sorriso123', 'attends_patients' => false]);
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['name' => 'Ana', 'password' => 'Sorriso123']);
        $this->felipe = User::factory()->create(['name' => 'Felipe', 'password' => 'Sorriso123']);

        $this->anaAppointment = Appointment::factory()->create([
            'professional_id' => $this->ana->id,
            'patient_id' => Patient::factory()->create(['name' => 'Paciente da Ana'])->id,
        ]);
        $this->felipeAppointment = Appointment::factory()->create([
            'professional_id' => $this->felipe->id,
            'patient_id' => Patient::factory()->create(['name' => 'Paciente do Felipe'])->id,
        ]);
    });

    $this->signIn = fn (User $user) => $this->clinicRequest('sorriso', 'POST', 'login', [
        'email' => $user->email,
        'password' => 'Sorriso123',
    ])->assertOk();
});

it('shows a professional only the own agenda', function () {
    ($this->signIn)($this->ana);

    $this->clinicRequest('sorriso', 'GET', 'agenda')
        ->assertOk()
        ->assertJsonCount(1, 'events')
        ->assertJsonPath('events.0.patientName', 'Paciente da Ana')
        ->assertJsonPath('events.0.professionalId', (string) $this->ana->id)
        ->assertJsonCount(1, 'professionals')
        ->assertJsonPath('professionals.0.name', 'Ana')
        ->assertJsonMissing(['patientName' => 'Paciente do Felipe']);
});

it('ignores another professional id sent by a professional', function () {
    ($this->signIn)($this->ana);

    $this->clinicRequest('sorriso', 'GET', "agenda?professional_id={$this->felipe->id}")
        ->assertOk()
        ->assertJsonCount(1, 'events')
        ->assertJsonPath('events.0.professionalId', (string) $this->ana->id)
        ->assertJsonMissing(['patientName' => 'Paciente do Felipe']);
});

it('shows the admin and the reception everyone', function (string $who) {
    ($this->signIn)($this->{$who});

    $this->clinicRequest('sorriso', 'GET', 'agenda')
        ->assertOk()
        ->assertJsonCount(2, 'events')
        ->assertJsonCount(2, 'professionals');
})->with(['admin', 'receptionist']);

it('lets the admin filter by professional', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'GET', "agenda?professional_id={$this->felipe->id}")
        ->assertOk()
        ->assertJsonCount(1, 'events')
        ->assertJsonPath('events.0.patientName', 'Paciente do Felipe');
});

it('follows the shape the frontend expects', function () {
    ($this->signIn)($this->admin);

    $this->clinicRequest('sorriso', 'GET', 'agenda')
        ->assertOk()
        ->assertJsonPath('today', today()->toDateString())
        ->assertJsonStructure([
            'today', 'from', 'to', 'hours', 'freeSlotsToday', 'blocks',
            'procedures' => [['key', 'label', 'duration']],
            'professionals' => [['id', 'name', 'tone', 'selected']],
            'rooms' => [['id', 'name', 'selected']],
            'events' => [['id', 'patientName', 'date', 'hour', 'endHour', 'duration', 'procedure', 'procedureKey', 'status', 'statusLabel', 'source', 'professionalId', 'roomId']],
        ])
        ->assertJsonPath('events.0.hour', '09:00')
        ->assertJsonPath('events.0.endHour', '10:00')
        ->assertJsonPath('events.0.statusLabel', 'Agendado')
        ->assertJsonPath('hours.0', '08:00')
        ->assertJsonPath('hours.1', '08:30');
});

it('leaves out cancelled and rejected appointments and those outside the period', function () {
    $this->clinic->run(function () {
        Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->create(['professional_id' => $this->ana->id]);
        Appointment::factory()->withStatus(AppointmentStatus::Rejected)->create(['professional_id' => $this->ana->id]);
        Appointment::factory()->create(['professional_id' => $this->ana->id, 'starts_at' => today()->subMonths(6)]);
        Appointment::factory()->create(['professional_id' => $this->ana->id, 'starts_at' => today()->addMonths(6)]);
    });

    ($this->signIn)($this->ana);

    $this->clinicRequest('sorriso', 'GET', 'agenda')->assertOk()->assertJsonCount(1, 'events');
});

it('does not run a query per appointment', function () {
    ($this->signIn)($this->admin);

    $countQueries = function () {
        $this->resetRequestState();
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });
        $this->clinicRequest('sorriso', 'GET', 'agenda')->assertOk();

        return $queries;
    };

    $few = $countQueries();
    $this->clinic->run(fn () => Appointment::factory()->count(10)->create(['professional_id' => $this->ana->id]));
    $many = $countQueries();

    expect($many)->toBe($few);
});

it('returns only the requested period', function () {
    $this->clinic->run(fn () => Appointment::factory()->at(today()->addDays(20)->setTime(9, 0)->toDateTimeString())->create(['professional_id' => $this->ana->id]));

    ($this->signIn)($this->admin);

    $from = today()->addDays(19)->toDateString();
    $to = today()->addDays(21)->toDateString();

    $this->clinicRequest('sorriso', 'GET', "agenda?from={$from}&to={$to}")
        ->assertOk()
        ->assertJsonCount(1, 'events')
        ->assertJsonPath('from', $from);

    $tooLong = today()->addDays(90)->toDateString();

    $this->clinicRequest('sorriso', 'GET', "agenda?from={$from}&to={$tooLong}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['to']);
});

it('requires authentication', function () {
    $this->clinicRequest('sorriso', 'GET', 'agenda')->assertUnauthorized();
});
