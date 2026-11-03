<?php

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Procedure;
use App\Domains\Agenda\Models\Room;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-09 07:00'));

    $this->clinic = $this->createClinic('sorriso');

    $this->clinic->run(function () {
        $this->admin = User::factory()->admin()->create(['password' => 'Sorriso123', 'attends_patients' => false]);
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['password' => 'Sorriso123']);
        $this->room = Room::factory()->create(['name' => 'Sala 01']);
        $this->patient = Patient::factory()->create();
    });
});

it('starts every clinic with the procedures that lived in code', function () {
    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'GET', 'procedures')
        ->assertOk()
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('data.0.name', 'Avaliação')
        ->assertJsonPath('data.0.durationMinutes', 30)
        ->assertJsonPath('data.0.active', true);
});

it('lets only admins change procedures and rooms', function () {
    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'POST', 'procedures', ['name' => 'Clareamento', 'duration_minutes' => 90, 'price_cents' => 45000])->assertForbidden();
    $this->clinicRequest('sorriso', 'POST', 'rooms', ['name' => 'Sala 03'])->assertForbidden();
    $this->clinicRequest('sorriso', 'GET', 'rooms')->assertOk();

    $this->signIn('sorriso', $this->admin);

    $this->clinicRequest('sorriso', 'POST', 'procedures', ['name' => 'Clareamento', 'duration_minutes' => 90, 'price_cents' => 45000])
        ->assertCreated()
        ->assertJsonPath('priceCents', 45000)
        ->assertJsonPath('active', true);

    $this->clinicRequest('sorriso', 'POST', 'rooms', ['name' => 'Sala 03'])->assertCreated()->assertJsonPath('name', 'Sala 03');
});

it('validates length, price and repeated names', function () {
    $this->signIn('sorriso', $this->admin);

    $this->clinicRequest('sorriso', 'POST', 'procedures', ['name' => 'Avaliação', 'duration_minutes' => 45, 'price_cents' => -1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name' => 'Já existe um procedimento ativo com este nome.',
            'duration_minutes' => 'Escolha uma duração entre 30 minutos e 4 horas, em intervalos de 30 minutos.',
            'price_cents' => 'Informe um preço válido.',
        ]);

    $this->clinicRequest('sorriso', 'POST', 'rooms', ['name' => 'Sala 01'])
        ->assertJsonValidationErrors(['name' => 'Já existe uma sala ativa com este nome.']);
});

it('keeps the price and length of booked appointments', function () {
    $procedure = $this->clinic->run(fn () => Procedure::factory()->create(['name' => 'Clareamento', 'duration_minutes' => 90, 'price_cents' => 45000]));

    $this->signIn('sorriso', $this->admin);

    $booked = $this->clinicRequest('sorriso', 'POST', 'appointments', [
        'patient_id' => $this->patient->id,
        'professional_id' => $this->ana->id,
        'room_id' => $this->room->id,
        'procedure_id' => $procedure->id,
        'date' => '2026-11-10',
        'time' => '09:00',
    ])
        ->assertCreated()
        ->assertJsonPath('priceCents', 45000)
        ->assertJsonPath('endHour', '10:30')
        ->json('id');

    $this->clinicRequest('sorriso', 'PUT', "procedures/{$procedure->id}", ['name' => 'Clareamento', 'duration_minutes' => 60, 'price_cents' => 50000])->assertOk();

    $this->clinicRequest('sorriso', 'GET', "appointments/{$booked}")
        ->assertJsonPath('priceCents', 45000)
        ->assertJsonPath('duration', 90);
});

it('stops offering inactive procedures and rooms', function () {
    [$procedure, $room] = $this->clinic->run(fn () => [
        Procedure::factory()->inactive()->create(['name' => 'Antigo']),
        Room::factory()->create(['name' => 'Sala Fechada', 'active' => false]),
    ]);

    $this->signIn('sorriso', $this->admin);

    $this->clinicRequest('sorriso', 'POST', 'appointments', [
        'patient_id' => $this->patient->id,
        'professional_id' => $this->ana->id,
        'room_id' => $room->id,
        'procedure_id' => $procedure->id,
        'date' => '2026-11-10',
        'time' => '09:00',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'procedure_id' => 'Escolha um procedimento oferecido pela clínica.',
            'room_id' => 'Escolha uma sala em uso na clínica.',
        ]);

    $agenda = $this->clinicRequest('sorriso', 'GET', 'agenda')->json();

    expect(collect($agenda['procedures'])->pluck('label'))->not->toContain('Antigo')
        ->and(collect($agenda['rooms'])->firstWhere('name', 'Sala Fechada')['active'])->toBeFalse();

    expect(collect($this->clinicRequest('sorriso', 'GET', 'public/booking')->json('procedures'))->pluck('label'))->not->toContain('Antigo');
});

it('keeps procedures and rooms that were used', function () {
    $this->clinic->run(fn () => Appointment::factory()->create(['room_id' => $this->room->id]));
    $procedureId = $this->procedureId($this->clinic, 'Limpeza e prevenção');
    $unused = $this->procedureId($this->clinic, 'Consulta de retorno');

    $this->signIn('sorriso', $this->admin);

    $this->clinicRequest('sorriso', 'DELETE', "procedures/{$procedureId}")
        ->assertJsonValidationErrors(['procedure' => 'Este procedimento já foi usado em consultas. Desative-o em vez de excluí-lo.']);
    $this->clinicRequest('sorriso', 'DELETE', "rooms/{$this->room->id}")
        ->assertJsonValidationErrors(['room' => 'Esta sala já foi usada em consultas. Desative-a em vez de excluí-la.']);
    $this->clinicRequest('sorriso', 'DELETE', "procedures/{$unused}")->assertNoContent();
});

it('converts the stored procedure keys into the new table', function () {
    $clinic = $this->createClinic('legado');

    $clinic->run(function () {
        $this->artisan('migrate:rollback', ['--path' => database_path('migrations/tenant/2026_11_03_000000_create_procedures_table.php'), '--realpath' => true, '--database' => 'tenant'])->assertSuccessful();

        DB::table('appointments')->insert([
            'patient_id' => Patient::factory()->create()->id,
            'professional_id' => User::factory()->create()->id,
            // The model already knows the active column, which the rolled-back table does not have.
            'room_id' => DB::table('rooms')->insertGetId(['name' => 'Sala antiga', 'created_at' => now(), 'updated_at' => now()]),
            'procedure' => 'aesthetic_restoration',
            'starts_at' => '2026-11-10 09:00',
            'ends_at' => '2026-11-10 10:30',
            'status' => 'scheduled',
            'source' => 'internal',
            'tone' => 'blue',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('migrate', ['--path' => database_path('migrations/tenant/2026_11_03_000000_create_procedures_table.php'), '--realpath' => true, '--database' => 'tenant'])->assertSuccessful();

        expect(Appointment::query()->sole()->procedure->name)->toBe('Restauração estética');
    });
});
