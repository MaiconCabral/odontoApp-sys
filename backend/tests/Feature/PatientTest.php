<?php

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Identity\Models\User;

beforeEach(function () {
    $this->clinic = $this->createClinic('sorriso');

    $this->clinic->run(function () {
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->professional = User::factory()->create(['password' => 'Sorriso123']);
    });
});

it('creates a patient with a normalized CPF and e-mail', function () {
    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'POST', 'patients', [
        'name' => 'Mariana Costa',
        'phone' => '(11) 99843-2108',
        'email' => ' Mariana@Email.com ',
        'cpf' => '12345678901',
        'insurance' => 'SulAmérica',
        'birth_date' => '1990-05-20',
    ])
        ->assertCreated()
        ->assertJsonPath('cpf', '123.456.789-01')
        ->assertJsonPath('email', 'mariana@email.com')
        ->assertJsonPath('initials', 'MC')
        ->assertJsonPath('isPrivate', false)
        ->assertJsonPath('lastAppointment', 'Sem consultas');
});

it('refuses a repeated CPF and invalid data', function () {
    $this->clinic->run(fn () => Patient::factory()->create(['cpf' => '123.456.789-01']));

    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'POST', 'patients', ['name' => 'X', 'phone' => '12', 'cpf' => '123.456.789-01', 'email' => 'nao'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name' => 'Informe o nome completo.',
            'phone' => 'Informe um telefone com DDD.',
            'cpf' => 'Já existe um paciente com este CPF.',
            'email' => 'Informe um e-mail válido.',
        ]);
});

it('searches by name, CPF digits or phone digits, page by page', function () {
    $this->clinic->run(function () {
        Patient::factory()->create(['name' => 'Mariana Costa', 'cpf' => '111.222.333-44', 'phone' => '(11) 90000-1111']);
        Patient::factory()->count(25)->create();
    });

    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'GET', 'patients')
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('meta.total', 26)
        ->assertJsonPath('meta.last_page', 2);

    foreach (['mariana', '11122233344', '900001111'] as $term) {
        $this->clinicRequest('sorriso', 'GET', "patients?search={$term}")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mariana Costa');
    }
});

it('shows the date of the last visit', function () {
    $patient = $this->clinic->run(function () {
        $patient = Patient::factory()->create();
        Appointment::factory()->at('2026-01-15 09:00')->create(['patient_id' => $patient->id]);
        Appointment::factory()->at(now()->addMonth()->setTime(9, 0)->toDateTimeString())->create(['patient_id' => $patient->id]);

        return $patient;
    });

    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'GET', 'patients')->assertJsonPath('data.0.lastAppointment', '15/01/2026');
});

it('keeps a patient who has appointments', function () {
    [$withHistory, $without] = $this->clinic->run(function () {
        $patient = Patient::factory()->create();
        Appointment::factory()->create(['patient_id' => $patient->id]);

        return [$patient, Patient::factory()->create()];
    });

    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'DELETE', "patients/{$withHistory->id}")
        ->assertJsonValidationErrors(['patient' => 'Este paciente tem consultas registradas e não pode ser excluído.']);

    $this->clinicRequest('sorriso', 'DELETE', "patients/{$without->id}")->assertNoContent();
});

it('keeps patient records away from professionals', function () {
    $patient = $this->clinic->run(fn () => Patient::factory()->create());

    $this->signIn('sorriso', $this->professional);

    $this->clinicRequest('sorriso', 'GET', 'patients')->assertForbidden();
    $this->clinicRequest('sorriso', 'GET', "patients/{$patient->id}")->assertForbidden();
    $this->clinicRequest('sorriso', 'POST', 'patients', ['name' => 'Novo Paciente', 'phone' => '11999990000'])->assertForbidden();
    $this->clinicRequest('sorriso', 'DELETE', "patients/{$patient->id}")->assertForbidden();
});
