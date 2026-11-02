<?php

namespace App\Domains\Agenda\Actions;

use App\Domains\Agenda\Models\BusinessHour;
use Illuminate\Support\Facades\DB;

class UpdateBusinessHours
{
    /**
     * Replaces the opening hours of each weekday. Booked appointments are kept as they are.
     *
     * @param  list<array<string, mixed>>  $days
     */
    public function handle(array $days): void
    {
        DB::transaction(function () use ($days) {
            foreach ($days as $day) {
                $closed = (bool) $day['closed'];

                BusinessHour::query()->updateOrCreate(['weekday' => $day['weekday']], [
                    'closed' => $closed,
                    'opens_at' => $closed ? null : $day['opens_at'],
                    'closes_at' => $closed ? null : $day['closes_at'],
                    'break_starts_at' => $closed ? null : ($day['break_starts_at'] ?? null),
                    'break_ends_at' => $closed ? null : ($day['break_ends_at'] ?? null),
                ]);
            }
        });
    }
}
