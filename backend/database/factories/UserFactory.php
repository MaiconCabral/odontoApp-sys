<?php

namespace Database\Factories;

use App\Domains\Identity\Enums\UserRole;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Professional,
            'specialty' => null,
            'attends_patients' => true,
            'tone' => 'cyan',
            'active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /** Administrator. */
    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::Admin]);
    }

    /** Reception user who does not attend. */
    public function reception(): static
    {
        return $this->state(fn () => ['role' => UserRole::Reception, 'attends_patients' => false]);
    }

    /** Deactivated account. */
    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
