<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
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
            'email' => fake()->unique()->safeEmail(),
            'username' => fake()->unique()->regexify('[a-z]{8}'),
            'password' => static::$password ??= Hash::make('password'),
            'full_name' => fake()->name(),
            'birth_date' => fake()->date(max: '-16 years'),
            'xp' => 0,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Beheerder van het admin-dashboard.
     */
    public function admin(): static
    {
        return $this->state(fn (): array => ['is_admin' => true]);
    }
}
