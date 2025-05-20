<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'causer' => User::factory(),
            'target_type' => 'profile',
            'target_id' => $this->faker->userName,
            'content' => $this->faker->sentence,
        ];
    }
}
