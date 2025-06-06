<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Profile;
use App\Models\Subscription;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = Profile::pluck('nickname')->toArray();

        if (count($profiles) < 2) {
            $this->command->warn('Not enough profiles to create subscriptions.');
            return;
        }

        for ($i = 0; $i < 30; $i++) {
            $causer = fake()->randomElement($profiles);
            $author = fake()->randomElement($profiles);

            if ($causer === $author) {
                continue;
            }

            Subscription::firstOrCreate([
                'causer' => $causer,
                'author' => $author,
            ]);
        }
    }
}
