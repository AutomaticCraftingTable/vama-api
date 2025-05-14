<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nickname' => $this->faker->unique()->userName(),
            'description' => $this->faker->sentence(),
            'logo' => $this->faker->imageUrl(100, 100, 'logo'),
        ];
    }
}
