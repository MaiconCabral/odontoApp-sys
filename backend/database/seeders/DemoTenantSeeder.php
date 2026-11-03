<?php

namespace Database\Seeders;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Procedure;
use App\Domains\Agenda\Models\Room;
use App\Domains\Agenda\Models\ScheduleBlock;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Plan;
use App\Domains\Tenancy\Models\Tenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use RuntimeException;

// Demo clinics for local development, taken from the data the frontend mocked.
class DemoTenantSeeder extends Seeder
{
    private const CLINICS = [
        [
            'id' => 'demo-sorriso-perfeito',
            'slug' => 'sorriso-perfeito',
            'clinic' => [
                'name' => 'Sorriso Perfeito',
                'unit' => 'Unidade Jardins',
                'cnpj' => '12.345.678/0001-90',
                'phone' => '(11) 3051-8090',
                'email' => 'contato@sorrisoperfeito.com',
                'address' => 'Rua Oscar Freire, 1240 · Jardins, São Paulo - SP',
            ],
            'users' => [
                ['name' => 'Dr. Carlos Mendes', 'email' => 'demo@sorriso.app', 'role' => UserRole::Admin, 'specialty' => 'Ortodontia', 'attends' => true, 'tone' => 'cyan'],
                ['name' => 'Dra. Ana Beatriz', 'email' => 'ana@sorriso.app', 'role' => UserRole::Professional, 'specialty' => 'Odontologia estética', 'attends' => true, 'tone' => 'amber'],
                ['name' => 'Dr. Felipe Rocha', 'email' => 'felipe@sorriso.app', 'role' => UserRole::Professional, 'specialty' => 'Cirurgia', 'attends' => true, 'tone' => 'green'],
                ['name' => 'Paula Nunes', 'email' => 'recepcao@sorriso.app', 'role' => UserRole::Reception, 'specialty' => null, 'attends' => false, 'tone' => 'cyan'],
            ],
            'rooms' => ['Sala 01 · Consultório', 'Sala 02 · Cirurgia'],
            'patients' => [
                ['name' => 'Mariana Costa', 'cpf' => '000.000.000-01', 'phone' => '(11) 99843-2108', 'insurance' => 'SulAmérica', 'email' => 'mariana@paciente.test'],
                ['name' => 'Rafael Oliveira', 'cpf' => '000.000.000-02', 'phone' => '(11) 99127-7844', 'insurance' => 'Particular', 'email' => 'rafael@paciente.test'],
                ['name' => 'Camila Ferreira', 'cpf' => '000.000.000-03', 'phone' => '(11) 99632-4510', 'insurance' => 'Bradesco Saúde', 'email' => 'camila@paciente.test'],
                ['name' => 'Lucas Martins', 'cpf' => '000.000.000-04', 'phone' => '(11) 98901-6288', 'insurance' => 'Amil'],
                ['name' => 'Juliana Almeida', 'cpf' => '000.000.000-05', 'phone' => '(11) 99731-0972', 'insurance' => 'Particular'],
                ['name' => 'Bruno Martins', 'cpf' => null, 'phone' => '(11) 98455-1203', 'insurance' => 'Particular'],
                ['name' => 'Letícia Souza', 'cpf' => null, 'phone' => '(11) 97321-8840', 'insurance' => 'SulAmérica'],
            ],
        ],
        [
            'id' => 'demo-clinica-horizonte',
            'slug' => 'clinica-horizonte',
            'clinic' => [
                'name' => 'Clínica Horizonte',
                'unit' => 'Unidade Centro',
                'cnpj' => '98.765.432/0001-10',
                'phone' => '(21) 3200-4500',
                'email' => 'contato@clinicahorizonte.com',
                'address' => 'Av. Rio Branco, 156 · Centro, Rio de Janeiro - RJ',
            ],
            'users' => [
                ['name' => 'Dra. Helena Duarte', 'email' => 'helena@horizonte.app', 'role' => UserRole::Admin, 'specialty' => 'Implantodontia', 'attends' => true, 'tone' => 'cyan'],
                ['name' => 'Dr. Tiago Freitas', 'email' => 'tiago@horizonte.app', 'role' => UserRole::Professional, 'specialty' => 'Endodontia', 'attends' => true, 'tone' => 'amber'],
            ],
            'rooms' => ['Consultório A'],
            'patients' => [
                ['name' => 'Roberto Lima', 'cpf' => null, 'phone' => '(21) 99811-2030', 'insurance' => 'Particular'],
                ['name' => 'Sofia Andrade', 'cpf' => null, 'phone' => '(21) 98822-4410', 'insurance' => 'Amil'],
                ['name' => 'Eduardo Pires', 'cpf' => null, 'phone' => '(21) 97733-5520', 'insurance' => 'Particular'],
            ],
        ],
    ];

    private const PROCEDURES = ['Avaliação', 'Limpeza e prevenção', 'Restauração estética', 'Consulta de retorno'];

    // Demo prices in cents. The migration creates the procedures with price zero.
    private const PRICES = [
        'Avaliação' => 15000,
        'Avaliação ortodôntica' => 25000,
        'Limpeza e prevenção' => 20000,
        'Restauração estética' => 45000,
        'Consulta de retorno' => 0,
    ];

    // One start time per professional, outside the lunch break and inside Saturday morning.
    private const START_TIMES = ['08:30', '10:00', '09:00'];

    /** Recreates the demo clinics. */
    public function run(): void
    {
        $password = env('SEED_DEMO_PASSWORD');

        if (app()->isProduction() || ! $password) {
            throw new RuntimeException('Demo clinics need a non-production environment and SEED_DEMO_PASSWORD in .env.');
        }

        foreach (self::CLINICS as $clinic) {
            $this->seedClinic($clinic, $password);
        }
    }

    /**
     * Creates one demo clinic with its subdomain and data.
     *
     * @param  array<string, mixed>  $clinic
     */
    private function seedClinic(array $clinic, string $password): void
    {
        $this->removePrevious($clinic['id']);

        $tenant = Tenant::create([
            ...$clinic['clinic'],
            'id' => $clinic['id'],
            'plan_id' => Plan::query()->where('code', Plan::TRIAL_CODE)->valueOrFail('id'),
            'trial_ends_at' => now()->addDays(14),
        ]);
        $tenant->createDomain(['domain' => $clinic['slug']]);

        $tenant->run(fn () => $this->seedClinicData($clinic, $password));
    }

    /**
     * Deletes the previous demo clinic and any orphan database.
     * migrate:fresh drops the central tables but not the clinic databases, which would then be orphaned.
     */
    private function removePrevious(string $id): void
    {
        Tenant::find($id)?->delete();

        $orphan = new Tenant(['id' => $id]);
        $manager = $orphan->database()->manager();

        if ($manager->databaseExists($orphan->database()->getName())) {
            $manager->deleteDatabase($orphan);
        }
    }

    /**
     * Creates users, rooms and patients of a demo clinic.
     *
     * @param  array<string, mixed>  $clinic
     */
    private function seedClinicData(array $clinic, string $password): void
    {
        $professionals = collect($clinic['users'])
            ->map(fn (array $user) => User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'email_verified_at' => now(),
                'password' => $password,
                'role' => $user['role'],
                'specialty' => $user['specialty'],
                'attends_patients' => $user['attends'],
                'tone' => $user['tone'],
            ]))
            ->filter(fn (User $user) => $user->attends_patients)
            ->values();

        $rooms = collect($clinic['rooms'])->map(fn (string $name) => Room::create(['name' => $name]));
        $patients = collect($clinic['patients'])->map(fn (array $patient) => Patient::create($patient));

        $procedures = $this->pricedProcedures();

        $this->seedAppointments($professionals, $rooms, $patients, $procedures);
        $this->seedRequests($professionals, $patients, $procedures);
        $this->seedBlocks($professionals);
    }

    /**
     * Creates the demo appointments.
     * Each professional gets one appointment per weekday, from three days ago to ten days ahead.
     * Past ones are completed or missed; upcoming ones are scheduled or confirmed.
     */
    private function seedAppointments(Collection $professionals, Collection $rooms, Collection $patients, Collection $procedures): void
    {
        $sequence = 0;

        foreach (range(-3, 10) as $dayOffset) {
            $day = CarbonImmutable::today()->addDays($dayOffset);

            if ($day->isSunday()) {
                continue;
            }

            foreach ($professionals as $index => $professional) {
                $procedure = $procedures[self::PROCEDURES[$sequence % count(self::PROCEDURES)]];
                $startsAt = $day->setTimeFromTimeString(self::START_TIMES[$index % count(self::START_TIMES)]);

                // Saturday closes at noon, so the long procedure moves to a shorter one.
                if ($day->isSaturday() && $startsAt->addMinutes($procedure->minutes())->format('H:i') > '12:00') {
                    $procedure = $procedures['Consulta de retorno'];
                }

                $appointment = new Appointment([
                    'patient_id' => $patients[$sequence % $patients->count()]->id,
                    'professional_id' => $professional->id,
                    'room_id' => $rooms[$index % $rooms->count()]->id,
                    'procedure_id' => $procedure->id,
                    'price_cents' => $procedure->price_cents,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->addMinutes($procedure->minutes()),
                    'source' => Appointment::SOURCE_INTERNAL,
                ]);
                $appointment->moveTo(AppointmentStatus::Scheduled, null);
                $this->advance($appointment, $startsAt, $sequence);

                $sequence++;
            }
        }
    }

    /** Moves a demo appointment along the flow, so every status shows up in the agenda. */
    private function advance(Appointment $appointment, CarbonImmutable $startsAt, int $sequence): void
    {
        if ($startsAt->isPast()) {
            $appointment->moveTo($sequence % 5 === 0 ? AppointmentStatus::NoShow : AppointmentStatus::Completed, null);

            return;
        }

        if ($sequence % 3 === 0) {
            $appointment->moveTo(AppointmentStatus::Confirmed, null, 'Confirmado por telefone.');
        }
    }

    /** Two patient requests from the public page, waiting for the reception. */
    private function seedRequests(Collection $professionals, Collection $patients, Collection $procedures): void
    {
        foreach ([2 => '15:00', 3 => '16:00'] as $dayOffset => $time) {
            $day = CarbonImmutable::today()->addWeekdays($dayOffset);
            $startsAt = $day->setTimeFromTimeString($day->isSaturday() ? '10:30' : $time);
            $procedure = $procedures['Avaliação'];

            $request = new Appointment([
                'patient_id' => $patients[$dayOffset % $patients->count()]->id,
                'professional_id' => $professionals[$dayOffset % $professionals->count()]->id,
                'procedure_id' => $procedure->id,
                'price_cents' => $procedure->price_cents,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->addMinutes($procedure->minutes()),
                'source' => Appointment::SOURCE_PUBLIC,
                'notes' => 'Pedido feito pela página de agendamento.',
            ]);
            $request->moveTo(AppointmentStatus::Requested, null);
        }
    }

    /**
     * Gives the procedures created by the migration their demo prices.
     *
     * @return Collection<string, Procedure>
     */
    private function pricedProcedures(): Collection
    {
        foreach (self::PRICES as $name => $price) {
            Procedure::query()->where('name', $name)->update(['price_cents' => $price]);
        }

        return Procedure::query()->get()->keyBy('name');
    }

    /** A clinic holiday and a day off, both after the seeded appointments. */
    private function seedBlocks(Collection $professionals): void
    {
        $holiday = CarbonImmutable::today()->addWeekdays(10);

        ScheduleBlock::create([
            'starts_at' => $holiday->startOfDay(),
            'ends_at' => $holiday->endOfDay(),
            'reason' => 'Feriado municipal',
        ]);

        $dayOff = CarbonImmutable::today()->addWeekdays(11);

        ScheduleBlock::create([
            'professional_id' => $professionals->last()->id,
            'starts_at' => $dayOff->startOfDay(),
            'ends_at' => $dayOff->endOfDay(),
            'reason' => 'Folga',
        ]);
    }
}
