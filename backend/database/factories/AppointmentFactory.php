<?php

namespace Database\Factories;

use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Room;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * One-hour appointment today at 09:00.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = today()->setTime(9, 0);

        return [
            'patient_id' => Patient::factory(),
            'professional_id' => User::factory(),
            'room_id' => Room::factory(),
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'procedure' => 'Consulta',
            'status' => Appointment::STATUS_SCHEDULED,
            'tone' => 'blue',
        ];
    }
}
