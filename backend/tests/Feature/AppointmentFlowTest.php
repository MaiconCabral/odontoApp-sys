<?php

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Room;
use App\Domains\Agenda\Models\ScheduleBlock;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;

// Monday, 9 November 2026, before the clinic opens. Tomorrow is a regular Tuesday.
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-09 07:00'));

    $this->clinic = $this->createClinic('sorriso');

    $this->clinic->run(function () {
        $this->admin = User::factory()->admin()->create(['password' => 'Sorriso123', 'attends_patients' => false]);
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['name' => 'Ana', 'password' => 'Sorriso123']);
        $this->felipe = User::factory()->create(['name' => 'Felipe', 'password' => 'Sorriso123']);
        $this->room = Room::factory()->create(['name' => 'Sala 01']);
        $this->otherRoom = Room::factory()->create(['name' => 'Sala 02']);
        $this->patient = Patient::factory()->create(['name' => 'Camila Ferreira']);
    });

    $this->booking = fn (array $overrides = []) => [
        'patient_id' => $this->patient->id,
        'professional_id' => $this->ana->id,
        'room_id' => $this->room->id,
        'procedure' => 'cleaning',
        'date' => '2026-11-10',
        'time' => '09:00',
        ...$overrides,
    ];
});

it('books an appointment and records the history', function () {
    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'POST', 'appointments', ($this->booking)())
        ->assertCreated()
        ->assertJsonPath('status', 'scheduled')
        ->assertJsonPath('statusLabel', 'Agendado')
        ->assertJsonPath('hour', '09:00')
        ->assertJsonPath('endHour', '10:00')
        ->assertJsonPath('patient.name', 'Camila Ferreira')
        ->assertJsonPath('history.0.toLabel', 'Agendado')
        ->assertJsonPath('history.0.author', $this->receptionist->name)
        ->assertJsonPath('actions', ['confirm', 'cancel', 'reschedule']);
});

it('refuses times the clinic does not serve', function (array $overrides, string $field, string $message) {
    $this->signIn('sorriso', $this->admin);

    $this->clinicRequest('sorriso', 'POST', 'appointments', ($this->booking)($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field => $message]);
})->with([
    'past' => [['date' => '2026-11-08', 'time' => '09:00'], 'starts_at', 'Escolha um horário futuro.'],
    'sunday' => [['date' => '2026-11-15'], 'starts_at', 'A clínica não atende neste dia.'],
    'before opening' => [['time' => '07:30'], 'starts_at', 'Fora do horário de funcionamento da clínica.'],
    'past closing' => [['time' => '17:30'], 'starts_at', 'Fora do horário de funcionamento da clínica.'],
    'saturday afternoon' => [['date' => '2026-11-14', 'time' => '11:30'], 'starts_at', 'Fora do horário de funcionamento da clínica.'],
    'lunch break' => [['time' => '11:30'], 'starts_at', 'Este horário cai no intervalo de almoço da clínica.'],
    'off the grid' => [['time' => '09:15'], 'starts_at', 'Escolha um horário em intervalos de 30 minutos.'],
]);

it('refuses clinic closures and professional days off', function () {
    $this->clinic->run(function () {
        ScheduleBlock::create(['starts_at' => '2026-11-11 00:00', 'ends_at' => '2026-11-11 23:59', 'reason' => 'Feriado']);
        ScheduleBlock::create(['professional_id' => $this->ana->id, 'starts_at' => '2026-11-12 00:00', 'ends_at' => '2026-11-12 23:59', 'reason' => 'Folga']);
    });

    $this->signIn('sorriso', $this->admin);

    $this->clinicRequest('sorriso', 'POST', 'appointments', ($this->booking)(['date' => '2026-11-11']))
        ->assertJsonValidationErrors(['starts_at' => 'A clínica está fechada neste horário: Feriado.']);

    $this->clinicRequest('sorriso', 'POST', 'appointments', ($this->booking)(['date' => '2026-11-12']))
        ->assertJsonValidationErrors(['professional_id' => 'O profissional não atende neste horário: Folga.']);

    $this->clinicRequest('sorriso', 'POST', 'appointments', ($this->booking)(['date' => '2026-11-12', 'professional_id' => $this->felipe->id]))
        ->assertCreated();
});

it('refuses a busy professional or room, but not a cancelled slot', function () {
    $this->clinic->run(fn () => Appointment::factory()->at('2026-11-10 09:30')->create([
        'professional_id' => $this->ana->id,
        'room_id' => $this->room->id,
    ]));

    $this->signIn('sorriso', $this->admin);

    $this->clinicRequest('sorriso', 'POST', 'appointments', ($this->booking)())
        ->assertJsonValidationErrors([
            'professional_id' => 'Este profissional já tem consulta neste horário.',
            'room_id' => 'Esta sala já está ocupada neste horário.',
        ]);

    $this->clinicRequest('sorriso', 'POST', 'appointments', ($this->booking)(['professional_id' => $this->felipe->id, 'room_id' => $this->otherRoom->id]))
        ->assertCreated();

    $this->clinic->run(fn () => Appointment::query()->where('professional_id', $this->ana->id)->update(['status' => AppointmentStatus::Cancelled]));

    $this->clinicRequest('sorriso', 'POST', 'appointments', ($this->booking)())->assertCreated();
});

it('validates the booking form', function () {
    $this->signIn('sorriso', $this->admin);

    $this->clinicRequest('sorriso', 'POST', 'appointments', ['procedure' => 'botox', 'professional_id' => $this->receptionist->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['patient_id', 'room_id', 'date', 'time', 'procedure'])
        ->assertJsonValidationErrors(['professional_id' => 'Escolha um profissional da clínica.']);
});

it('moves an appointment and sends a confirmed one back to scheduled', function () {
    $appointment = $this->clinic->run(fn () => Appointment::factory()
        ->withStatus(AppointmentStatus::Confirmed)
        ->at('2026-11-10 09:00')
        ->create(['professional_id' => $this->ana->id, 'room_id' => $this->room->id, 'patient_id' => $this->patient->id]));

    $this->signIn('sorriso', $this->receptionist);

    // The appointment does not collide with itself when it only grows or shifts a little.
    $this->clinicRequest('sorriso', 'PUT', "appointments/{$appointment->id}", ($this->booking)(['time' => '09:30']))
        ->assertOk()
        ->assertJsonPath('hour', '09:30')
        ->assertJsonPath('status', 'scheduled')
        ->assertJsonPath('history.0.reason', 'Remarcada de 10/11/2026 09:00 para 10/11/2026 09:30.');
});

it('follows the status flow', function () {
    $appointment = $this->clinic->run(fn () => Appointment::factory()->at('2026-11-10 09:00')->create(['professional_id' => $this->ana->id]));

    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'POST', "appointments/{$appointment->id}/status", ['status' => 'confirmed'])
        ->assertOk()
        ->assertJsonPath('statusLabel', 'Confirmado');

    $this->clinicRequest('sorriso', 'POST', "appointments/{$appointment->id}/status", ['status' => 'rejected', 'reason' => 'x'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status' => 'Não é possível mudar de Confirmado para Recusado.']);

    $this->clinicRequest('sorriso', 'POST', "appointments/{$appointment->id}/status", ['status' => 'completed'])
        ->assertJsonValidationErrors(['status' => 'Só é possível marcar isso depois do início da consulta.']);

    $this->clinicRequest('sorriso', 'POST', "appointments/{$appointment->id}/status", ['status' => 'cancelled'])
        ->assertJsonValidationErrors(['reason' => 'Informe o motivo.']);

    $this->clinicRequest('sorriso', 'POST', "appointments/{$appointment->id}/status", ['status' => 'cancelled', 'reason' => 'Paciente viajou'])
        ->assertOk()
        ->assertJsonPath('cancelReason', 'Paciente viajou')
        ->assertJsonPath('actions', [])
        ->assertJsonCount(2, 'history');
});

it('lets a professional finish only their own appointments', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-10 10:30'));

    [$own, $other] = $this->clinic->run(fn () => [
        Appointment::factory()->at('2026-11-10 09:00')->create(['professional_id' => $this->ana->id]),
        Appointment::factory()->at('2026-11-10 09:00')->create(['professional_id' => $this->felipe->id]),
    ]);

    $this->signIn('sorriso', $this->ana);

    $this->clinicRequest('sorriso', 'GET', "appointments/{$own->id}")
        ->assertOk()
        ->assertJsonPath('actions', ['complete', 'no_show']);

    $this->clinicRequest('sorriso', 'POST', "appointments/{$own->id}/status", ['status' => 'cancelled', 'reason' => 'x'])->assertForbidden();
    $this->clinicRequest('sorriso', 'GET', "appointments/{$other->id}")->assertForbidden();
    $this->clinicRequest('sorriso', 'POST', "appointments/{$other->id}/status", ['status' => 'completed'])->assertForbidden();
    $this->clinicRequest('sorriso', 'POST', 'appointments', ($this->booking)())->assertForbidden();

    $this->clinicRequest('sorriso', 'POST', "appointments/{$own->id}/status", ['status' => 'no_show'])
        ->assertOk()
        ->assertJsonPath('statusLabel', 'Faltou');
});

it('accepts a request in a free room and refuses a busy one', function () {
    [$request] = $this->clinic->run(fn () => [
        Appointment::factory()->requested()->at('2026-11-10 09:00')->create(['professional_id' => $this->ana->id]),
        Appointment::factory()->at('2026-11-10 09:00')->create(['professional_id' => $this->felipe->id, 'room_id' => $this->room->id]),
    ]);

    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'GET', 'appointment-requests')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.actions', ['accept', 'reject', 'cancel']);

    $this->clinicRequest('sorriso', 'POST', "appointments/{$request->id}/accept", ['room_id' => $this->room->id])
        ->assertJsonValidationErrors(['room_id' => 'Esta sala já está ocupada neste horário.']);

    $this->clinicRequest('sorriso', 'POST', "appointments/{$request->id}/accept", ['room_id' => $this->otherRoom->id])
        ->assertOk()
        ->assertJsonPath('status', 'scheduled')
        ->assertJsonPath('room.name', 'Sala 02');

    $this->clinicRequest('sorriso', 'POST', "appointments/{$request->id}/accept", ['room_id' => $this->otherRoom->id])
        ->assertJsonValidationErrors(['status' => 'Este pedido já foi respondido.']);

    $this->clinicRequest('sorriso', 'GET', 'appointment-requests')->assertJsonCount(0, 'data');
});

it('rejects a request with a reason', function () {
    $request = $this->clinic->run(fn () => Appointment::factory()->requested()->at('2026-11-10 09:00')->create(['professional_id' => $this->ana->id]));

    $this->signIn('sorriso', $this->admin);

    $this->clinicRequest('sorriso', 'POST', "appointments/{$request->id}/status", ['status' => 'scheduled'])
        ->assertJsonValidationErrors(['status' => 'Para aceitar um pedido, escolha a sala.']);

    $this->clinicRequest('sorriso', 'POST', "appointments/{$request->id}/status", ['status' => 'rejected', 'reason' => 'Agenda cheia'])
        ->assertOk()
        ->assertJsonPath('statusLabel', 'Recusado');
});

it('keeps the requests and the status changes inside the clinic', function () {
    $this->createClinic('outra')->run(fn () => User::factory()->reception()->create(['email' => 'recepcao@outra.app', 'password' => 'Sorriso123']));
    $appointment = $this->clinic->run(fn () => Appointment::factory()->requested()->at('2026-11-10 09:00')->create(['professional_id' => $this->ana->id]));

    $this->clinicRequest('outra', 'POST', 'login', ['email' => 'recepcao@outra.app', 'password' => 'Sorriso123'])->assertOk();

    $this->clinicRequest('outra', 'GET', 'appointment-requests')->assertOk()->assertJsonCount(0, 'data');
    $this->clinicRequest('outra', 'GET', "appointments/{$appointment->id}")->assertNotFound();
});
