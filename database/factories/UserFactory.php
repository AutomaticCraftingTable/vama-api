<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
    'email' => fake()->unique()->safeEmail(),
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
    'role' => 'user',
    'banned_at' => null,
    'google_id' => null,
];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            "email_verified_at" => null,
        ]);
    }
}
