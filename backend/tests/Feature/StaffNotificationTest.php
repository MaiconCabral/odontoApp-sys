<?php

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Enums\StaffMessage;
use App\Domains\Notifications\Notifications\StaffAlert;
use App\Domains\Notifications\Support\PatientLinks;
use Carbon\CarbonImmutable;
use Illuminate\Notifications\DatabaseNotification;

// Monday, 9 November 2026. The alerts are real rows here, not a fake.
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-09 10:00'));

    $this->clinic = $this->createClinic('sorriso');

    $this->clinic->run(function () {
        $this->admin = User::factory()->admin()->create(['password' => 'Sorriso123']);
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['name' => 'Ana', 'password' => 'Sorriso123']);
        $this->felipe = User::factory()->create(['name' => 'Felipe', 'password' => 'Sorriso123']);
        $this->former = User::factory()->reception()->inactive()->create();
        $this->patient = Patient::factory()->create(['name' => 'Camila Ferreira', 'email' => null]);
    });

    $this->alertsOf = fn (User $user) => $this->clinic->run(
        fn () => DatabaseNotification::query()->where('notifiable_id', $user->id)->pluck('type')->all(),
    );
});

it('alerts admins and reception about a new booking request', function () {
    $this->clinicRequest('sorriso', 'POST', 'public/booking-requests', [
        'name' => 'Paciente Novo',
        'phone' => '(11) 99999-0000',
        'email' => null,
        'procedure_id' => $this->procedureId($this->clinic, 'Avaliação'),
        'professional_id' => $this->ana->id,
        'date' => '2026-11-10',
        'time' => '09:00',
        'consent' => true,
        'website' => '',
    ])->assertCreated();

    expect(($this->alertsOf)($this->admin))->toBe([StaffMessage::BookingRequested->value])
        ->and(($this->alertsOf)($this->receptionist))->toBe([StaffMessage::BookingRequested->value])
        ->and(($this->alertsOf)($this->ana))->toBe([])
        ->and(($this->alertsOf)($this->former))->toBe([]);
});

it('alerts the staff and the professional when the patient cancels', function () {
    $token = $this->clinic->run(function () {
        $appointment = Appointment::factory()->at('2026-11-10 09:00')->create([
            'patient_id' => $this->patient->id,
            'professional_id' => $this->ana->id,
        ]);

        return app(PatientLinks::class)->token($appointment);
    });

    $this->clinicRequest('sorriso', 'POST', "public/appointments/{$token}/cancel")->assertOk();

    expect(($this->alertsOf)($this->ana))->toBe([StaffMessage::CancelledByPatient->value])
        ->and(($this->alertsOf)($this->receptionist))->toBe([StaffMessage::CancelledByPatient->value])
        ->and(($this->alertsOf)($this->felipe))->toBe([]);
});

it('does not alert anyone when the clinic itself cancels', function () {
    $appointment = $this->clinic->run(fn () => Appointment::factory()->at('2026-11-10 09:00')->create([
        'patient_id' => $this->patient->id,
        'professional_id' => $this->ana->id,
    ]));

    $this->signIn('sorriso', $this->receptionist);
    $this->clinicRequest('sorriso', 'POST', "appointments/{$appointment->id}/status", ['status' => 'cancelled', 'reason' => 'Agenda fechada'])
        ->assertOk();

    expect(($this->alertsOf)($this->ana))->toBe([]);
});

describe('bell', function () {
    beforeEach(function () {
        $this->clinic->run(function () {
            foreach ([$this->receptionist, $this->ana] as $user) {
                $user->notify(new StaffAlert(StaffMessage::ConfirmedByPatient, [
                    'appointmentId' => '7',
                    'date' => '2026-11-10',
                    'title' => 'Paciente confirmou a consulta',
                    'body' => 'Camila Ferreira confirmou a consulta.',
                ]));
            }

            $this->receptionist->notify(new StaffAlert(StaffMessage::BookingRequested, [
                'appointmentId' => '8',
                'date' => '2026-11-11',
                'title' => 'Novo pedido de agendamento',
                'body' => 'Paciente Novo pediu um horário.',
            ]));
        });
    });

    it('lists the alerts of the signed-in user with the unread count', function () {
        $this->signIn('sorriso', $this->receptionist);

        $this->clinicRequest('sorriso', 'GET', 'notifications')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.unreadCount', 2)
            ->assertJsonPath('data.0.read', false)
            ->assertJsonStructure(['data' => [['id', 'type', 'title', 'body', 'appointmentId', 'date', 'read', 'createdAt']]]);
    });

    it('marks one alert as read', function () {
        $this->signIn('sorriso', $this->receptionist);
        $id = $this->clinicRequest('sorriso', 'GET', 'notifications')->json('data.0.id');

        $this->clinicRequest('sorriso', 'POST', "notifications/{$id}/read")->assertNoContent();

        $this->clinicRequest('sorriso', 'GET', 'notifications')->assertJsonPath('meta.unreadCount', 1);
    });

    it('marks every alert as read without touching other users', function () {
        $this->signIn('sorriso', $this->receptionist);

        $this->clinicRequest('sorriso', 'POST', 'notifications/read-all')->assertNoContent();
        $this->clinicRequest('sorriso', 'GET', 'notifications')->assertJsonPath('meta.unreadCount', 0);

        $this->signIn('sorriso', $this->ana);
        $this->clinicRequest('sorriso', 'GET', 'notifications')->assertJsonCount(1, 'data')->assertJsonPath('meta.unreadCount', 1);
    });

    it('refuses to read the alert of another user', function () {
        $foreign = $this->clinic->run(fn () => $this->ana->notifications()->value('id'));
        $this->signIn('sorriso', $this->receptionist);

        $this->clinicRequest('sorriso', 'POST', "notifications/{$foreign}/read")->assertNotFound();
    });

    it('requires a session', function () {
        $this->clinicRequest('sorriso', 'GET', 'notifications')->assertUnauthorized();
    });
});
