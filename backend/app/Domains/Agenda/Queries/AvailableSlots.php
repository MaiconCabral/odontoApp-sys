<?php

namespace App\Domains\Agenda\Queries;

use App\Domains\Agenda\Support\DaySchedule;
use App\Domains\Identity\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class AvailableSlots
{
    /**
     * Free start times of a day, with the professionals free at each one.
     *
     * @return list<array{time: string, professionalIds: list<string>}>
     */
    public function for(CarbonInterface $date, int $minutes, ?int $professionalId = null, ?int $roomId = null, ?int $ignoreAppointmentId = null): array
    {
        $schedule = DaySchedule::load($date, $ignoreAppointmentId);
        $candidates = $professionalId ? [$professionalId] : $this->attendingIds();
        $slots = [];

        foreach ($schedule->slotStarts() as $start) {
            if ($start <= now()) {
                continue;
            }

            $free = array_values(array_filter(
                $candidates,
                fn (int $candidate) => $schedule->problems($start, $minutes, $candidate, $roomId) === [],
            ));

            if ($free) {
                $slots[] = ['time' => $start->format('H:i'), 'professionalIds' => array_map('strval', $free)];
            }
        }

        return $slots;
    }

    /** Free 30-minute slots of a day, summed over the given professionals. */
    public function countFree(CarbonImmutable $date, array $professionalIds): int
    {
        $schedule = DaySchedule::load($date);
        $count = 0;

        foreach ($schedule->slotStarts() as $start) {
            if ($start <= now()) {
                continue;
            }

            foreach ($professionalIds as $professionalId) {
                $count += $schedule->problems($start, DaySchedule::SLOT_MINUTES, (int) $professionalId, null) === [] ? 1 : 0;
            }
        }

        return $count;
    }

    /**
     * Active users who attend patients.
     *
     * @return list<int>
     */
    public function attendingIds(): array
    {
        return User::query()->where('attends_patients', true)->where('active', true)->orderBy('id')->pluck('id')->all();
    }
}
