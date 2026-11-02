<?php

namespace Database\Factories;

use App\Domains\Agenda\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    /**
     * Room with a numbered name.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['name' => 'Sala '.fake()->unique()->numberBetween(1, 99)];
    }
}
