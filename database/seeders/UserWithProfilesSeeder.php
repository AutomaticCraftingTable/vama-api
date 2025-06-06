<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserWithProfilesSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('test@example.com'),
            'email_verified_at' => now(),
            'role' => 'superadmin',
            'google_id' => null,
            'remember_token' => Str::random(10),
        ]);
        Profile::factory()->for($superadmin)->create();

        $admins = User::factory()
            ->count(3)
            ->state(['role' => 'admin'])
            ->create();

        $admins->each(
            fn ($user) =>
            Profile::factory()->for($user)->create()
        );

        $moderators = User::factory()
            ->count(5)
            ->state(['role' => 'moderator'])
            ->create();

        $moderators->each(
            fn ($user) =>
            Profile::factory()->for($user)->create()
        );

        $users = User::factory()
                   ->count(20)
                   ->state([
                       'role' => 'user',
                       'email_verified_at' => now(),
                   ])
                   ->create();

        $users->each(
            fn ($user) =>
            Profile::factory()->for($user)->create()
        );
    }
}
