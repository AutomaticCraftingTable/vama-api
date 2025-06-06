<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;

class LogVerifiedUser
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        DB::table('activities')->insert([
            'log_name' => 'auth',
            'description' => 'User verified their email',
            'subject_id' => $user->id,
            'subject_type' => get_class($user),
            'causer_id' => $user->id,
            'causer_type' => get_class($user),
            'event' => 'verified',
            'properties' => json_encode([
                'email' => $user->email,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
