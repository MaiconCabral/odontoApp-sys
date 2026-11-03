<?php

use App\Domains\Agenda\Enums\WaitlistStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Room;
use App\Domains\Agenda\Models\WaitlistEntry;
use App\Domains\Identity\Models\User;
use App\Domains\Notifications\Enums\StaffMessage;
use App\Domains\Notifications\Notifications\StaffAlert;
use App\Domains\Notifications\Notifications\WaitlistOffer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

// Monday, 9 November 2026. Ana has a consultation on Tuesday at 09:00 that may be cancelled.
beforeEach(function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-11-09 10:00'));

    $this->clinic = $this->createClinic('sorriso');
    $this->evaluation = $this->procedureId($this->clinic, 'Avaliação');

    $this->clinic->run(function () {
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['name' => 'Ana', 'password' => 'Sorriso123']);
        $this->felipe = User::factory()->create(['name' => 'Felipe']);
        $this->room = Room::factory()->create();
        $this->booked = Appointment::factory()->at('2026-11-10 09:00')->create([
            'professional_id' => $this->ana->id,
            'room_id' => $this->room->id,
            'procedure_id' => $this->evaluation,
        ]);
    });

    $this->entry = fn (array $overrides = []) => $this->clinic->run(fn () => WaitlistEntry::create([
        'patient_id' => Patient::factory()->create(['email' => fake()->unique()->safeEmail()])->id,
        'procedure_id' => $this->evaluation,
        'from_date' => '2026-11-09',
        'to_date' => '2026-11-20',
        'period' => 'any',
        'status' => 'waiting',
        ...$overrides,
    ]));

    $this->cancel = function () {
        $this->signIn('sorriso', $this->receptionist);
        $this->clinicRequest('sorriso', 'POST', "appointments/{$this->booked->id}/status", ['status' => 'cancelled', 'reason' => 'Paciente viajou'])
            ->assertOk();
    };
});

it('offers the freed time to the oldest matching patients, at most three', function () {
    $entries = collect(range(1, 4))->map(fn () => ($this->entry)());

    ($this->cancel)();

    foreach ($entries->take(3) as $entry) {
        Notification::assertSentTo($entry->patient, WaitlistOffer::class, fn (WaitlistOffer $offer) => str_contains($offer->details['link'], '/agendar?date=2026-11-10&time=09%3A00&professional_id='.$this->ana->id)
            && $offer->details['when'] === 'terça-feira, 10/11 às 09:00');
    }

    Notification::assertNotSentTo($entries->last()->patient, WaitlistOffer::class);
    Notification::assertSentTo($this->receptionist, StaffAlert::class, fn (StaffAlert $alert) => $alert->message === StaffMessage::WaitlistOffered);

    $statuses = $this->clinic->run(fn () => WaitlistEntry::query()->orderBy('id')->pluck('status')->map->value->all());

    expect($statuses)->toBe(['notified', 'notified', 'notified', 'waiting']);
});

it('skips entries for other days, periods, professionals or longer procedures', function () {
    $longer = $this->procedureId($this->clinic, 'Restauração estética');
    $skipped = collect([
        ($this->entry)(['from_date' => '2026-11-11']),
        ($this->entry)(['period' => 'afternoon']),
        ($this->entry)(['professional_id' => $this->felipe->id]),
        ($this->entry)(['procedure_id' => $longer]),
        ($this->entry)(['patient_id' => $this->clinic->run(fn () => Patient::factory()->create(['email' => null])->id)]),
    ]);
    $match = ($this->entry)(['period' => 'morning', 'professional_id' => $this->ana->id]);

    ($this->cancel)();

    Notification::assertSentTo($match->patient, WaitlistOffer::class);
    $skipped->each(fn (WaitlistEntry $entry) => Notification::assertNotSentTo($entry->patient, WaitlistOffer::class));
});

it('offers nothing when the clinic turned the waiting list off', function () {
    $entry = ($this->entry)();
    $this->clinic->update(['waiting_list' => false]);

    ($this->cancel)();

    Notification::assertNotSentTo($entry->patient, WaitlistOffer::class);
});

it('offers the old time when the appointment moves', function () {
    $entry = ($this->entry)();
    $this->signIn('sorriso', $this->receptionist);

    $this->clinicRequest('sorriso', 'PUT', "appointments/{$this->booked->id}", [
        'patient_id' => $this->booked->patient_id,
        'professional_id' => $this->ana->id,
        'room_id' => $this->room->id,
        'procedure_id' => $this->evaluation,
        'date' => '2026-11-12',
        'time' => '14:00',
    ])->assertOk();

    Notification::assertSentTo($entry->patient, WaitlistOffer::class, fn (WaitlistOffer $offer) => $offer->details['when'] === 'terça-feira, 10/11 às 09:00');
});

describe('api', function () {
    it('adds, lists, edits and removes entries', function () {
        $patient = $this->clinic->run(fn () => Patient::factory()->create(['name' => 'Bruno Lima']));
        $this->signIn('sorriso', $this->receptionist);

        $id = $this->clinicRequest('sorriso', 'POST', 'waitlist', [
            'patient_id' => $patient->id,
            'procedure_id' => $this->evaluation,
            'from_date' => '2026-11-10',
            'to_date' => '2026-11-30',
            'period' => 'morning',
        ])->assertCreated()->assertJsonPath('statusLabel', 'Aguardando')->assertJsonPath('patient.name', 'Bruno Lima')->json('id');

        $this->clinicRequest('sorriso', 'GET', 'waitlist')->assertJsonCount(1, 'data');

        $this->clinicRequest('sorriso', 'PUT', "waitlist/{$id}", [
            'patient_id' => $patient->id,
            'procedure_id' => $this->evaluation,
            'from_date' => '2026-11-10',
            'to_date' => '2026-11-30',
            'period' => 'any',
            'status' => 'booked',
        ])->assertOk()->assertJsonPath('status', WaitlistStatus::Booked->value);

        $this->clinicRequest('sorriso', 'GET', 'waitlist')->assertJsonCount(0, 'data');
        $this->clinicRequest('sorriso', 'GET', 'waitlist?status=booked')->assertJsonCount(1, 'data');

        $this->clinicRequest('sorriso', 'DELETE', "waitlist/{$id}")->assertNoContent();
    });

    it('validates the range of days', function () {
        $this->signIn('sorriso', $this->receptionist);

        $this->clinicRequest('sorriso', 'POST', 'waitlist', [
            'patient_id' => $this->booked->patient_id,
            'procedure_id' => $this->evaluation,
            'from_date' => '2026-11-08',
            'to_date' => '2027-06-01',
            'period' => 'any',
            'status' => 'booked',
        ])->assertUnprocessable()->assertJsonValidationErrors(['from_date', 'to_date', 'status']);
    });

    it('is closed to professionals', function () {
        $this->signIn('sorriso', $this->ana);

        $this->clinicRequest('sorriso', 'GET', 'waitlist')->assertForbidden();
    });
});
