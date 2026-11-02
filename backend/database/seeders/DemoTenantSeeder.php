<?php

namespace Database\Seeders;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Room;
use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use App\Domains\Tenancy\Models\Plan;
use App\Domains\Tenancy\Models\Tenant;
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
            'patients' => ['Mariana Costa', 'Rafael Oliveira', 'Camila Ferreira', 'Juliana Almeida', 'Bruno Martins', 'Letícia Souza'],
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
            'patients' => ['Roberto Lima', 'Sofia Andrade', 'Eduardo Pires'],
        ],
    ];

    private const PROCEDURES = ['Avaliação', 'Limpeza e prevenção', 'Restauração estética', 'Consulta de retorno'];

    private const EVENT_TONES = ['blue', 'amber', 'violet'];

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
        $patients = collect($clinic['patients'])->map(fn (string $name) => Patient::create(['name' => $name]));

        $this->seedAppointments($professionals, $rooms, $patients);
    }

    /**
     * Each professional gets one appointment per day for the next few days, at different hours.
     */
    private function seedAppointments(Collection $professionals, Collection $rooms, Collection $patients): void
    {
        $sequence = 0;

        foreach (range(-2, 7) as $dayOffset) {
            foreach ($professionals as $professionalIndex => $professional) {
                $startsAt = today()->addDays($dayOffset)->setTime(9 + $professionalIndex * 2 + ($sequence % 2), 0);

                Appointment::create([
                    'patient_id' => $patients[$sequence % $patients->count()]->id,
                    'professional_id' => $professional->id,
                    'room_id' => $rooms[$professionalIndex % $rooms->count()]->id,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addHour(),
                    'procedure' => self::PROCEDURES[$sequence % count(self::PROCEDURES)],
                    'tone' => self::EVENT_TONES[$sequence % count(self::EVENT_TONES)],
                ]);

                $sequence++;
            }
        }
    }
}
