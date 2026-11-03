<?php

namespace Database\Factories;

use App\Domains\Agenda\Models\Procedure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Procedure>
 */
class ProcedureFactory extends Factory
{
    protected $model = Procedure::class;

    /**
     * One-hour procedure with a price.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Procedimento '.fake()->unique()->numberBetween(1, 999),
            'duration_minutes' => 60,
            'price_cents' => 20000,
            'active' => true,
        ];
    }

    /** Procedure no longer offered. */
    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
