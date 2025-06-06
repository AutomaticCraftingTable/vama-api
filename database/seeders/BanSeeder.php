<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BanSeeder extends Seeder
{
    public function run(): void
    {
        $usersToBan = User::take(3)->get();

        $admin = User::where('role', 'admin')->first();

        if (!$admin) {
            $admin = User::factory()->create([
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
        }

        foreach ($usersToBan as $user) {
            DB::table('bans')->insert([
                'causer' => $admin->id,
                'target_type' => 'account',
                'target_id' => $user->id,
                'content' => 'Violation of terms of service.',
                'created_at' => now(),
            ]);
        }


        $articlesToBan = \App\Models\Article::take(3)->get();

        foreach ($articlesToBan as $article) {
            DB::table('bans')->insert([
                'causer' => $admin->id,
                'target_type' => 'article',
                'target_id' => $article->id,
                'content' => 'Article violates community standards.',
                'created_at' => now(),
            ]);
        }

        $commentsToBan = \App\Models\Comment::take(5)->get();

        foreach ($commentsToBan as $comment) {
            DB::table('bans')->insert([
                'causer' => $admin->id,
                'target_type' => 'comment',
                'target_id' => $comment->id,
                'content' => 'Comment contains spam or offensive language.',
                'created_at' => now(),
            ]);
        }
    }
}
