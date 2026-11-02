<?php

namespace Database\Factories;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Enums\Procedure;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Room;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    /**
     * One-hour cleaning today at 09:00.
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
            'ends_at' => $startsAt->copy()->addMinutes(Procedure::Cleaning->minutes()),
            'procedure' => Procedure::Cleaning,
            'status' => AppointmentStatus::Scheduled,
            'source' => Appointment::SOURCE_INTERNAL,
            'tone' => 'blue',
        ];
    }

    /** Appointment at a given start, keeping the procedure length. */
    public function at(string $startsAt): static
    {
        return $this->state(function (array $attributes) use ($startsAt) {
            $start = CarbonImmutable::parse($startsAt);
            $procedure = $attributes['procedure'] instanceof Procedure ? $attributes['procedure'] : Procedure::from($attributes['procedure']);

            return ['starts_at' => $start, 'ends_at' => $start->addMinutes($procedure->minutes())];
        });
    }

    /** Patient request waiting for an answer, without a room. */
    public function requested(): static
    {
        return $this->state(fn () => [
            'status' => AppointmentStatus::Requested,
            'source' => Appointment::SOURCE_PUBLIC,
            'room_id' => null,
        ]);
    }

    /** Appointment in a given status. */
    public function withStatus(AppointmentStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
