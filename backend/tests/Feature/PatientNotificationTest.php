<?php

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Room;
use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Actions\SendDueReminders;
use App\Domains\Notifications\Enums\PatientMessage;
use App\Domains\Notifications\Enums\StaffMessage;
use App\Domains\Notifications\Models\NotificationLog;
use App\Domains\Notifications\Notifications\AppointmentMessage;
use App\Domains\Notifications\Notifications\StaffAlert;
use App\Domains\Notifications\PatientNotifier;
use App\Domains\Notifications\Support\PatientLinks;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

// Monday, 9 November 2026, 07:00.
beforeEach(function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-11-09 07:00'));

    $this->clinic = $this->createClinic('sorriso');

    $this->clinic->run(function () {
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['name' => 'Dra. Ana', 'password' => 'Sorriso123']);
        $this->room = Room::factory()->create();
        $this->patient = Patient::factory()->create(['name' => 'Camila Ferreira', 'email' => 'camila@paciente.test']);
        $this->silent = Patient::factory()->create(['name' => 'Sem Email', 'email' => null]);
    });

    $this->booking = fn (array $overrides = []) => [
        'patient_id' => $this->patient->id,
        'professional_id' => $this->ana->id,
        'room_id' => $this->room->id,
        'procedure_id' => $this->procedureId($this->clinic, 'Limpeza e prevenção'),
        'date' => '2026-11-10',
        'time' => '09:00',
        ...$overrides,
    ];
});

// Messages sent to a patient, by type.
function sentTypes(Patient $patient): array
{
    return Notification::sent($patient, AppointmentMessage::class)->map(fn (AppointmentMessage $message) => $message->message)->all();
}

it('tells the patient when the clinic books, moves and cancels', function () {
    $this->signIn('sorriso', $this->receptionist);

    $id = $this->clinicRequest('sorriso', 'POST', 'appointments', ($this->booking)())->assertCreated()->json('id');
    $this->clinicRequest('sorriso', 'PUT', "appointments/{$id}", ($this->booking)(['time' => '10:00']))->assertOk();
    $this->clinicRequest('sorriso', 'POST', "appointments/{$id}/status", ['status' => 'cancelled', 'reason' => 'Agenda da dentista mudou'])->assertOk();

    expect(sentTypes($this->patient))->toBe([PatientMessage::Scheduled, PatientMessage::Rescheduled, PatientMessage::Cancelled]);

    Notification::assertSentTo($this->patient, AppointmentMessage::class, function (AppointmentMessage $message) {
        if ($message->message !== PatientMessage::Rescheduled) {
            return false;
        }

        expect($message->details['previous'])->toBe('terça-feira, 10/11 às 09:00')
            ->and($message->details['when'])->toBe('terça-feira, 10/11 às 10:00')
            ->and($message->details['link'])->toStartWith('http://sorriso.localhost:3000/consulta?token=');

        return true;
    });

    $this->clinicRequest('sorriso', 'GET', "appointments/{$id}")
        ->assertJsonCount(3, 'notifications')
        ->assertJsonPath('notifications.0.typeLabel', 'Consulta cancelada')
        ->assertJsonPath('notifications.0.statusLabel', 'Na fila de envio')
        ->assertJsonPath('patientHasEmail', true);
});

it('tells the patient about a public request and its answer', function () {
    $this->clinicRequest('sorriso', 'POST', 'public/booking-requests', [
        'name' => 'Camila Ferreira',
        'phone' => $this->patient->phone,
        'email' => 'camila@paciente.test',
        'procedure_id' => $this->procedureId($this->clinic, 'Avaliação'),
        'professional_id' => $this->ana->id,
        'date' => '2026-11-10',
        'time' => '14:00',
        'consent' => true,
    ])->assertCreated();

    $request = $this->clinic->run(fn () => Appointment::query()->sole());

    $this->signIn('sorriso', $this->receptionist);
    $this->clinicRequest('sorriso', 'POST', "appointments/{$request->id}/status", ['status' => 'rejected', 'reason' => 'Dentista em curso'])->assertOk();

    expect(sentTypes($this->patient))->toBe([PatientMessage::Requested, PatientMessage::Rejected]);

    Notification::assertSentTo($this->patient, AppointmentMessage::class, fn (AppointmentMessage $message) => $message->message === PatientMessage::Rejected
        && $message->details['reason'] === 'Dentista em curso');
});

it('records a skipped message when the patient has no e-mail', function () {
    $this->signIn('sorriso', $this->receptionist);

    $id = $this->clinicRequest('sorriso', 'POST', 'appointments', ($this->booking)(['patient_id' => $this->silent->id]))->json('id');

    Notification::assertNothingSent();

    $this->clinicRequest('sorriso', 'GET', "appointments/{$id}")
        ->assertJsonPath('patientHasEmail', false)
        ->assertJsonPath('notifications.0.status', NotificationLog::STATUS_SKIPPED);
});

it('sends nothing when the change is rolled back', function () {
    $this->clinic->run(function () {
        DB::transaction(function () {
            $appointment = Appointment::factory()->at('2026-11-10 09:00')->create(['patient_id' => $this->patient->id]);
            app(PatientNotifier::class)->send($appointment, PatientMessage::Scheduled);

            throw new RuntimeException('rollback');
        });
    });
})->throws(RuntimeException::class)->after(fn () => Notification::assertNothingSent());

it('builds the e-mail in Portuguese with the clinic as sender', function () {
    $message = new AppointmentMessage(PatientMessage::Reminder, [
        'clinic' => 'Sorriso Perfeito', 'address' => 'Rua A, 10', 'phone' => '(11) 3051-8090', 'patient' => 'Camila',
        'procedure' => 'Limpeza', 'professional' => 'Dra. Ana', 'when' => 'terça-feira, 10/11 às 09:00', 'reason' => null,
        'link' => 'http://sorriso.localhost:3000/consulta?token=x',
    ], ['mail'], 1);

    $mail = $message->toMail(new stdClass);

    expect($mail->subject)->toBe('Lembrete: sua consulta é amanhã - Sorriso Perfeito')
        ->and($mail->from[1])->toBe('Sorriso Perfeito')
        ->and($mail->actionUrl)->toBe('http://sorriso.localhost:3000/consulta?token=x')
        ->and(implode(' ', $mail->introLines))->toContain('terça-feira, 10/11 às 09:00');
});

describe('reminders', function () {
    beforeEach(function () {
        $this->clinic->run(function () {
            $make = fn (string $start, AppointmentStatus $status = AppointmentStatus::Scheduled) => Appointment::factory()
                ->withStatus($status)->at($start)->create(['patient_id' => $this->patient->id, 'professional_id' => $this->ana->id]);

            $this->tomorrow = $make('2026-11-10 06:30');
            $this->tooSoon = $make('2026-11-09 07:30');
            $this->tooFar = $make('2026-11-10 08:00');
            $this->cancelled = $make('2026-11-09 15:00', AppointmentStatus::Cancelled);
            $this->confirmed = $make('2026-11-09 16:00', AppointmentStatus::Confirmed);
        });
    });

    it('reminds once the appointments of the next 24 hours', function () {
        expect($this->clinic->run(fn () => app(SendDueReminders::class)->handle()))->toBe(2)
            ->and($this->clinic->run(fn () => app(SendDueReminders::class)->handle()))->toBe(0);

        Notification::assertSentToTimes($this->patient, AppointmentMessage::class, 2);

        $reminded = $this->clinic->run(fn () => Appointment::query()->whereNotNull('reminder_sent_at')->pluck('id')->sort()->values()->all());

        expect($reminded)->toBe(collect([$this->tomorrow->id, $this->confirmed->id])->sort()->values()->all());
    });

    it('runs for every clinic from the command', function () {
        $other = $this->createClinic('outra');
        $other->run(fn () => Appointment::factory()->at('2026-11-09 12:00')->create([
            'patient_id' => Patient::factory()->create(['email' => 'outro@paciente.test'])->id,
        ]));

        $this->artisan('appointments:send-reminders')
            ->expectsOutputToContain('Clínica sorriso: 2 lembrete(s).')
            ->expectsOutputToContain('Clínica outra: 1 lembrete(s).')
            ->assertSuccessful();
    });

    it('skips the clinics that turned the reminder off', function () {
        $this->clinic->update(['reminder_24h' => false]);

        $this->artisan('appointments:send-reminders')
            ->expectsOutputToContain('Clínica sorriso: lembretes desligados.')
            ->assertSuccessful();

        Notification::assertNothingSent();
    });

    it('warns the staff when a reminder fails', function () {
        $log = $this->clinic->run(fn () => NotificationLog::create([
            'appointment_id' => $this->tomorrow->id,
            'type' => PatientMessage::Reminder,
            'channel' => 'mail',
            'status' => NotificationLog::STATUS_QUEUED,
        ]));

        $this->clinic->run(fn () => (new AppointmentMessage(PatientMessage::Reminder, [], ['mail'], $log->id))->failed(new RuntimeException('SMTP fora')));

        expect($this->clinic->run(fn () => $log->fresh()->status))->toBe(NotificationLog::STATUS_FAILED);
        Notification::assertSentTo($this->receptionist, StaffAlert::class, fn (StaffAlert $alert) => $alert->message === StaffMessage::ReminderFailed);
    });

    it('sends a new reminder after the appointment moves', function () {
        $this->clinic->run(fn () => app(SendDueReminders::class)->handle());

        $this->signIn('sorriso', $this->receptionist);
        $this->clinicRequest('sorriso', 'PUT', "appointments/{$this->confirmed->id}", ($this->booking)([
            'date' => '2026-11-09', 'time' => '17:00',
        ]))->assertOk();

        expect($this->clinic->run(fn () => app(SendDueReminders::class)->handle()))->toBe(1);
    });
});

describe('patient links', function () {
    beforeEach(function () {
        [$this->appointment, $this->token] = $this->clinic->run(function () {
            $appointment = Appointment::factory()->at('2026-11-10 09:00')->create([
                'patient_id' => $this->patient->id,
                'professional_id' => $this->ana->id,
            ]);

            return [$appointment, app(PatientLinks::class)->token($appointment)];
        });
    });

    it('shows the appointment without a session', function () {
        $this->resetRequestState();

        $this->getJson("http://sorriso.localhost/api/public/appointments/{$this->token}")
            ->assertOk()
            ->assertJsonPath('when', 'terça-feira, 10/11 às 09:00')
            ->assertJsonPath('professional', 'Dra. Ana')
            ->assertJsonPath('canConfirm', true)
            ->assertJsonMissingPath('patient');
    });

    it('confirms once and keeps it confirmed', function () {
        $this->clinicRequest('sorriso', 'POST', "public/appointments/{$this->token}/confirm")
            ->assertOk()
            ->assertJsonPath('statusLabel', 'Confirmado')
            ->assertJsonPath('canConfirm', false);

        $this->clinicRequest('sorriso', 'POST', "public/appointments/{$this->token}/confirm")->assertOk()->assertJsonPath('status', 'confirmed');

        $history = $this->clinic->run(fn () => $this->appointment->statusChanges()->latest('id')->first());

        expect($history->changed_by)->toBeNull()
            ->and($history->reason)->toBe('Confirmado pelo paciente pelo link.');

        Notification::assertNothingSentTo($this->patient);
        Notification::assertSentToTimes($this->receptionist, StaffAlert::class, 1);
        Notification::assertSentTo($this->ana, StaffAlert::class, fn (StaffAlert $alert) => $alert->message === StaffMessage::ConfirmedByPatient);
    });

    it('cancels and frees the time', function () {
        $this->clinicRequest('sorriso', 'POST', "public/appointments/{$this->token}/cancel")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('canCancel', false);

        $this->clinicRequest('sorriso', 'POST', "public/appointments/{$this->token}/cancel")
            ->assertUnprocessable();
    });

    it('refuses unknown, tampered, moved, expired and foreign links', function () {
        $foreign = $this->createClinic('outra');

        $this->clinicRequest('sorriso', 'GET', 'public/appointments/nada')->assertNotFound();
        $this->clinicRequest('sorriso', 'GET', 'public/appointments/'.$this->appointment->id.'-'.str_repeat('a', 64))->assertNotFound();
        // A visitor of the other clinic has no session there; the test session would trip the clinic guard instead.
        $this->resetRequestState();
        $this->flushHeaders()->getJson("http://outra.localhost/api/public/appointments/{$this->token}")->assertNotFound();

        $this->clinic->run(fn () => $this->appointment->update(['starts_at' => '2026-11-10 10:00', 'ends_at' => '2026-11-10 11:00']));
        $this->clinicRequest('sorriso', 'GET', "public/appointments/{$this->token}")->assertNotFound();

        $fresh = $this->clinic->run(fn () => app(PatientLinks::class)->token($this->appointment->fresh()));
        $this->travelTo(CarbonImmutable::parse('2026-11-10 10:00'));
        $this->clinicRequest('sorriso', 'GET', "public/appointments/{$fresh}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Este link é inválido ou expirou.');

        expect($foreign->id)->not->toBe($this->clinic->id);
    });
});
