<?php

namespace Database\Factories;

use App\Domains\Agenda\Enums\AppointmentStatus;
use App\Domains\Agenda\Models\Appointment;
use App\Domains\Agenda\Models\Patient;
use App\Domains\Agenda\Models\Procedure;
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

    private const DEFAULT_MINUTES = 60;

    /**
     * One-hour cleaning today at 09:00, using the procedure every clinic database starts with.
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
            'procedure_id' => fn () => Procedure::query()->where('name', 'Limpeza e prevenção')->value('id') ?? Procedure::factory(),
            'price_cents' => 20000,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(self::DEFAULT_MINUTES),
            'status' => AppointmentStatus::Scheduled,
            'source' => Appointment::SOURCE_INTERNAL,
            'tone' => 'blue',
        ];
    }

    /** Appointment at a given start, keeping its length. */
    public function at(string $startsAt, int $minutes = self::DEFAULT_MINUTES): static
    {
        return $this->state(function () use ($startsAt, $minutes) {
            $start = CarbonImmutable::parse($startsAt);

            return ['starts_at' => $start, 'ends_at' => $start->addMinutes($minutes)];
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
