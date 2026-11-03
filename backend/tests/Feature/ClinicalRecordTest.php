<?php

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Clinical\Models\ClinicalNote;
use App\Domains\Clinical\Models\PatientAttachment;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

// Monday, 9 November 2026. Ana attended Camila; Felipe never did.
beforeEach(function () {
    Notification::fake();
    Storage::fake(PatientAttachment::DISK);
    $this->travelTo(CarbonImmutable::parse('2026-11-09 10:00'));

    $this->clinic = $this->createClinic('sorriso');

    $this->clinic->run(function () {
        $this->admin = User::factory()->admin()->create(['password' => 'Sorriso123']);
        $this->receptionist = User::factory()->reception()->create(['password' => 'Sorriso123']);
        $this->ana = User::factory()->create(['name' => 'Ana', 'password' => 'Sorriso123']);
        $this->felipe = User::factory()->create(['name' => 'Felipe', 'password' => 'Sorriso123']);
        $this->patient = Patient::factory()->create(['name' => 'Camila Ferreira']);
        $this->visit = Appointment::factory()->at('2026-11-02 09:00')->create([
            'patient_id' => $this->patient->id,
            'professional_id' => $this->ana->id,
        ]);
    });
});

describe('patient page', function () {
    it('lists the appointments of the patient, newest first', function () {
        $this->clinic->run(fn () => Appointment::factory()->at('2026-11-20 14:00')->create([
            'patient_id' => $this->patient->id,
            'professional_id' => $this->ana->id,
        ]));
        $this->signIn('sorriso', $this->receptionist);

        $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}/appointments")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.date', '2026-11-20')
            ->assertJsonPath('data.1.professional.name', 'Ana');
    });

    it('opens the patient to the professional who attended them, and only to them', function () {
        $this->signIn('sorriso', $this->ana);
        $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}")->assertOk();

        $this->signIn('sorriso', $this->felipe);
        $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}")->assertForbidden();
        $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}/appointments")->assertForbidden();
    });
});

describe('medical record', function () {
    it('keeps the clinical data away from reception and other professionals', function (string $who) {
        $this->signIn('sorriso', $this->{$who});

        foreach (['notes', 'odontogram', 'attachments'] as $part) {
            $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}/{$part}")->assertForbidden();
        }

        $this->clinicRequest('sorriso', 'POST', "patients/{$this->patient->id}/notes", ['body' => 'Nota'])->assertForbidden();
    })->with(['receptionist', 'felipe']);

    it('appends notes and corrections without changing the earlier ones', function () {
        $this->signIn('sorriso', $this->ana);

        $first = $this->clinicRequest('sorriso', 'POST', "patients/{$this->patient->id}/notes", [
            'body' => 'Sensibilidade no 16.',
            'appointment_id' => $this->visit->id,
        ])->assertCreated()->assertJsonPath('author', 'Ana')->assertJsonPath('appointment.date', '2026-11-02')->json('id');

        $this->clinicRequest('sorriso', 'POST', "patients/{$this->patient->id}/notes", [
            'body' => 'Correção: sensibilidade no 26.',
            'amends_id' => $first,
        ])->assertCreated()->assertJsonPath('amendsId', $first);

        $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}/notes")
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.body', 'Correção: sensibilidade no 26.')
            ->assertJsonPath('data.1.body', 'Sensibilidade no 16.');

        expect($this->clinic->run(fn () => ClinicalNote::query()->count()))->toBe(2);
    });

    it('refuses notes linked to another patient', function () {
        $other = $this->clinic->run(fn () => Appointment::factory()->at('2026-11-03 09:00')->create(['professional_id' => $this->ana->id]));
        $this->signIn('sorriso', $this->ana);

        $this->clinicRequest('sorriso', 'POST', "patients/{$this->patient->id}/notes", ['body' => 'Nota', 'appointment_id' => $other->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['appointment_id' => 'Escolha uma consulta deste paciente.']);
    });
});

describe('odontogram', function () {
    it('shows the latest condition of each tooth and surface, with the history', function () {
        $this->signIn('sorriso', $this->ana);
        $record = fn (array $data) => $this->clinicRequest('sorriso', 'POST', "patients/{$this->patient->id}/odontogram", $data)->assertCreated();

        $record(['tooth' => 16, 'surface' => 'O', 'condition' => 'caries']);
        $record(['tooth' => 16, 'surface' => 'O', 'condition' => 'restoration']);
        $record(['tooth' => 16, 'surface' => 'M', 'condition' => 'caries']);
        $record(['tooth' => 36, 'surface' => 'V', 'condition' => 'missing']);

        $response = $this->clinicRequest('sorriso', 'GET', "patients/{$this->patient->id}/odontogram")->assertOk();
        $current = collect($response->json('current'))->map(fn ($row) => "{$row['tooth']}{$row['surface']}:{$row['condition']}")->sort()->values()->all();

        expect($current)->toBe(['16M:caries', '16O:restoration', '36:missing'])
            ->and($response->json('history'))->toHaveCount(4);
    });

    it('validates the tooth and asks the surface of caries', function () {
        $this->signIn('sorriso', $this->admin);

        $this->clinicRequest('sorriso', 'POST', "patients/{$this->patient->id}/odontogram", ['tooth' => 19, 'condition' => 'caries'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['tooth' => 'Escolha um dente válido.', 'surface' => 'Escolha a face do dente.']);
    });
});

describe('attachments', function () {
    it('stores the file in the folder of the clinic and downloads it', function () {
        $this->signIn('sorriso', $this->ana);

        $id = $this->clinicRequest('sorriso', 'POST', "patients/{$this->patient->id}/attachments", [
            'file' => UploadedFile::fake()->create('raio-x.pdf', 120, 'application/pdf'),
        ])->assertCreated()->assertJsonPath('name', 'raio-x.pdf')->assertJsonPath('canDelete', false)->assertJsonMissingPath('path')->json('id');

        $path = $this->clinic->run(fn () => PatientAttachment::query()->find($id)->path);

        expect($path)->toStartWith("clinics/{$this->clinic->id}/patients/{$this->patient->id}/");
        Storage::disk(PatientAttachment::DISK)->assertExists($path);

        $this->clinicRequest('sorriso', 'GET', "attachments/{$id}/download")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDownload('raio-x.pdf');
    });

    it('refuses other types and big files', function () {
        $this->signIn('sorriso', $this->ana);

        $this->clinicRequest('sorriso', 'POST', "patients/{$this->patient->id}/attachments", ['file' => UploadedFile::fake()->create('script.exe', 10)])
            ->assertJsonValidationErrors(['file' => 'Envie um PDF ou uma imagem (JPG, PNG ou WebP).']);
        $this->clinicRequest('sorriso', 'POST', "patients/{$this->patient->id}/attachments", ['file' => UploadedFile::fake()->image('foto.png')->size(11000)])
            ->assertJsonValidationErrors(['file' => 'O arquivo pode ter até 10 MB.']);
    });

    it('lets only the admin delete a file, and removes it from the disk', function () {
        $this->signIn('sorriso', $this->ana);
        $id = $this->clinicRequest('sorriso', 'POST', "patients/{$this->patient->id}/attachments", [
            'file' => UploadedFile::fake()->image('foto.jpg'),
        ])->json('id');
        $path = $this->clinic->run(fn () => PatientAttachment::query()->find($id)->path);

        $this->clinicRequest('sorriso', 'DELETE', "attachments/{$id}")->assertForbidden();

        $this->signIn('sorriso', $this->admin);
        $this->clinicRequest('sorriso', 'DELETE', "attachments/{$id}")->assertNoContent();

        Storage::disk(PatientAttachment::DISK)->assertMissing($path);
    });

    it('does not serve a file of another clinic', function () {
        $this->signIn('sorriso', $this->admin);
        $id = $this->clinicRequest('sorriso', 'POST', "patients/{$this->patient->id}/attachments", [
            'file' => UploadedFile::fake()->image('foto.jpg'),
        ])->json('id');

        $other = $this->createClinic('outra');
        $admin = $other->run(fn () => User::factory()->admin()->create(['password' => 'Sorriso123']));
        // Another browser: the session of the first clinic is not carried over.
        $this->flushSession();
        $this->signIn('outra', $admin);

        $this->clinicRequest('outra', 'GET', "attachments/{$id}/download")->assertNotFound();
    });
});
