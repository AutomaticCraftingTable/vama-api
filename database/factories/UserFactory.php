<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
<<<<<<< Role
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'user',
            'banned_at' => null,
            'google_id' => null,
            'remember_token' => Str::random(10),
        ];
=======
    'email' => fake()->unique()->safeEmail(),
    'password' => bcrypt('password'),
    'email_verified_at' => now(),
    'role' => 'user',
    'banned_at' => null,
    'google_id' => null,
];
>>>>>>> main
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    public function banned(): static
    {
        return $this->state(fn () => [
            'banned_at' => now(),
        ]);
    }

    public function role(string $role): static
    {
        return $this->state(fn () => [
            'role' => $role,
        ]);
    }
}
