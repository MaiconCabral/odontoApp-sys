<?php

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Procedure;
use App\Domains\Clinical\Models\ClinicalNote;
use App\Domains\Clinical\Models\PatientAttachment;
use App\Domains\Finance\Models\Payment;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

// Behaviour behind the performance changes: bounded overlap, plain date ranges, caches and pages.

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-10 15:00'));

    $this->clinic = $this->createClinic('sorriso');
    $this->evaluation = $this->procedureId($this->clinic, 'Avaliação');

    $this->clinic->run(function () {
        $this->admin = User::factory()->admin()->create(['password' => 'Sorriso123']);
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['name' => 'Ana', 'password' => 'Sorriso123']);
        $this->patient = Patient::factory()->create(['name' => 'Camila Ferreira']);
    });
});

describe('overlapping appointments', function () {
    // Ids of the appointments the scope finds in the window.
    beforeEach(function () {
        $this->overlapping = fn (string $start, string $end) => $this->clinic->run(fn () => Appointment::query()
            ->overlapping(CarbonImmutable::parse($start), CarbonImmutable::parse($end))
            ->orderBy('id')
            ->pluck('id')
            ->all());

        $this->make = fn (string $start, int $minutes) => $this->clinic->run(fn () => Appointment::factory()->at($start, $minutes)
            ->create(['professional_id' => $this->ana->id])->id);
    });

    it('finds an appointment that started before the window and runs into it', function () {
        $crossing = ($this->make)('2026-11-10 07:30', 240);

        expect(($this->overlapping)('2026-11-10 09:00', '2026-11-10 10:00'))->toBe([$crossing]);
    });

    it('finds appointments inside and at the edges, but not the ones that only touch', function () {
        $inside = ($this->make)('2026-11-10 09:00', 30);
        $endsAtStart = ($this->make)('2026-11-10 08:00', 60);
        $startsAtEnd = ($this->make)('2026-11-10 10:00', 60);

        expect(($this->overlapping)('2026-11-10 09:00', '2026-11-10 10:00'))->toBe([$inside])
            ->not->toContain($endsAtStart)
            ->not->toContain($startsAtEnd);
    });

    it('leaves out the history before the window', function () {
        ($this->make)('2026-05-10 09:00', 60);
        ($this->make)('2026-11-09 09:00', 240);

        expect(($this->overlapping)('2026-11-10 00:00', '2026-11-11 00:00'))->toBe([]);
    });

    it('bounds the scan with a lower limit on starts_at', function () {
        $sql = $this->clinic->run(fn () => Appointment::query()->overlapping(now(), now()->addHour())->toSql());

        expect(substr_count($sql, '"starts_at"'))->toBe(2);
    });
});

describe('payments between days', function () {
    beforeEach(function () {
        $this->visit = $this->clinic->run(fn () => Appointment::factory()->withStatus(AppointmentStatus::Completed)
            ->at('2026-11-05 09:00')->create(['professional_id' => $this->ana->id, 'patient_id' => $this->patient->id]));

        $this->pay = fn (string $day, int $cents) => $this->clinic->run(fn () => Payment::create([
            'appointment_id' => $this->visit->id,
            'patient_id' => $this->patient->id,
            'amount_cents' => $cents,
            'method' => 'pix',
            'paid_at' => $day,
            'recorded_by' => $this->admin->id,
        ]));
    });

    it('includes both edge days and nothing outside them', function () {
        ($this->pay)('2026-10-31', 1);
        ($this->pay)('2026-11-01', 10);
        ($this->pay)('2026-11-15', 100);
        ($this->pay)('2026-11-30', 1000);
        ($this->pay)('2026-12-01', 10000);

        $sum = $this->clinic->run(fn () => Payment::query()
            ->paidBetween(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-30'))
            ->sum('amount_cents'));

        expect((int) $sum)->toBe(1110);
    });

    it('does not wrap paid_at in DATE() so the index is used', function () {
        $sql = $this->clinic->run(fn () => Payment::query()->paidBetween(now(), now())->toSql());

        expect(strtolower($sql))->not->toContain('date(')->and($sql)->toContain('"paid_at" >=');
    });

    it('reports only the money of the period, by day', function () {
        ($this->pay)('2026-10-31', 5000);
        ($this->pay)('2026-11-01', 7000);
        ($this->pay)('2026-11-07', 3000);

        $this->signIn('sorriso', $this->admin);
        $report = $this->clinicRequest('sorriso', 'GET', 'reports?from=2026-11-01&to=2026-11-07&group=day')->assertOk()->json();

        expect(json_encode($report))->toContain('10000')->not->toContain('15000');
    });
});

describe('procedure options cache', function () {
    it('serves the cached options until a procedure changes', function () {
        $this->signIn('sorriso', $this->admin);
        $labels = fn () => collect($this->clinicRequest('sorriso', 'GET', 'public/booking')->assertOk()->json('procedures'))->pluck('label');

        expect($labels())->toContain('Avaliação');

        $this->clinicRequest('sorriso', 'PUT', "procedures/{$this->evaluation}", [
            'name' => 'Avaliação inicial', 'duration_minutes' => 30, 'price_cents' => 15000, 'active' => true,
        ])->assertOk();

        expect($labels())->toContain('Avaliação inicial')->not->toContain('Avaliação');
    });

    it('keeps each clinic in its own cache key', function () {
        $other = $this->createClinic('outra');

        $this->clinic->run(fn () => Procedure::options());
        $other->run(fn () => Procedure::query()->whereKey($this->procedureId($other, 'Avaliação'))->update(['name' => 'Só da outra']));

        $keys = [
            $this->clinic->run(fn () => Procedure::optionsCacheKey()),
            $other->run(fn () => Procedure::optionsCacheKey()),
        ];

        expect($keys[0])->not->toBe($keys[1])
            ->and(Cache::has($keys[0]))->toBeTrue()
            ->and(Cache::has($keys[1]))->toBeFalse()
            ->and(collect($other->run(fn () => Procedure::options()))->pluck('label'))->toContain('Só da outra')
            ->and(collect($this->clinic->run(fn () => Procedure::options()))->pluck('label'))->not->toContain('Só da outra');
    });
});

describe('clinic lookup cache', function () {
    it('looks the clinic up once and forgets it when the clinic changes', function () {
        $lookups = fn () => collect($this->recordQueries(fn () => $this->clinicRequest('sorriso', 'POST', 'forgot-password', ['email' => 'nobody@example.com'])))
            ->filter(fn (string $sql) => str_contains($sql, '"domains"'))
            ->count();

        $lookups();
        expect($lookups())->toBe(0);

        $this->clinic->update(['name' => 'Clínica renomeada']);
        expect($lookups())->toBeGreaterThan(0);
    });
});

describe('pages of the patient record', function () {
    beforeEach(function () {
        $this->clinic->run(function () {
            foreach (range(1, 31) as $i) {
                ClinicalNote::create(['patient_id' => $this->patient->id, 'author_id' => $this->ana->id, 'body' => "Anotação {$i}"]);
                PatientAttachment::create([
                    'patient_id' => $this->patient->id, 'uploader_id' => $this->admin->id, 'original_name' => "exame-{$i}.pdf",
                    'path' => "attachments/exame-{$i}.pdf", 'mime' => 'application/pdf', 'size_bytes' => 100,
                ]);
            }
        });
    });

    it('sends the notes in pages of 30, newest first', function () {
        $this->signIn('sorriso', $this->admin);

        $first = $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}/notes")->assertOk();
        $first->assertJsonCount(30, 'data')->assertJsonPath('meta.last_page', 2)->assertJsonPath('data.0.body', 'Anotação 31');

        $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}/notes?page=2")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.body', 'Anotação 1');
    });

    it('sends the attachments in pages of 30, still telling who may delete them', function () {
        $this->signIn('sorriso', $this->admin);

        $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}/attachments")
            ->assertOk()->assertJsonCount(30, 'data')->assertJsonPath('meta.last_page', 2)->assertJsonPath('data.0.canDelete', true);

        // A professional sees the record of the patients they attend, but only the admin deletes files.
        $this->clinic->run(fn () => Appointment::factory()->at('2026-11-03 09:00')->create([
            'professional_id' => $this->ana->id, 'patient_id' => $this->patient->id,
        ]));
        $this->signIn('sorriso', $this->ana);
        $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}/attachments?page=2")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.canDelete', false);
    });

    it('keeps the record closed to the reception', function () {
        $this->signIn('sorriso', $this->receptionist);

        $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}/notes")->assertForbidden();
        $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}/attachments")->assertForbidden();
    });

    it('does not show one clinic the record of another', function () {
        $other = $this->createClinic('outra');
        $otherAdmin = $other->run(fn () => User::factory()->admin()->create(['password' => 'Sorriso123']));

        $this->signIn('outra', $otherAdmin);

        $this->clinicRequest('outra', 'GET', "patients/{$this->patient->id}/notes")->assertNotFound();
    });
});

describe('agenda options', function () {
    it('lists what the forms need without any appointment', function () {
        $this->clinic->run(fn () => Appointment::factory()->at('2026-11-10 09:00')->create(['professional_id' => $this->ana->id]));
        $this->signIn('sorriso', $this->receptionist);

        $options = $this->clinicRequest('sorriso', 'GET', 'agenda/options')->assertOk()->json();

        expect(array_keys($options))->toBe(['today', 'procedures', 'professionals', 'rooms'])
            ->and($options['today'])->toBe('2026-11-10')
            ->and(collect($options['procedures'])->pluck('label'))->toContain('Avaliação')
            ->and(collect($options['professionals'])->pluck('name'))->toContain('Ana');
    });

    it('shows a professional only themselves', function () {
        $this->clinic->run(fn () => User::factory()->create(['name' => 'Felipe']));
        $this->signIn('sorriso', $this->ana);

        $names = collect($this->clinicRequest('sorriso', 'GET', 'agenda/options')->assertOk()->json('professionals'))->pluck('name');

        expect($names->all())->toBe(['Ana']);
    });

    it('needs a signed-in user', function () {
        $this->clinicRequest('sorriso', 'GET', 'agenda/options')->assertUnauthorized();
    });

    it('runs the same queries however many appointments there are', function () {
        $this->signIn('sorriso', $this->admin);
        $count = fn () => $this->countQueries(fn () => $this->clinicRequest('sorriso', 'GET', 'agenda/options')->assertOk());

        $count();
        $few = $count();
        $this->clinic->run(fn () => Appointment::factory()->count(10)->create(['professional_id' => $this->ana->id]));

        expect($count())->toBe($few);
    });
});
