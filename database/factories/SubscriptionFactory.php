<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $causer = Profile::factory()->create();
        do {
            $author = Profile::factory()->create();
        } while ($author->nickname === $causer->nickname);

        return [
            'causer' => $causer->nickname,
            'author' => $author->nickname,
        ];
    }
}
