<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Profile;
use App\Models\Article;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $causerIds = User::pluck('id')->toArray();

        foreach (Profile::inRandomOrder()->take(5)->get() as $profile) {
            DB::table('reports')->insert([
                'causer' => fake()->randomElement($causerIds),
                'target_type' => 'profile',
                'target_id' => $profile->nickname,
                'content' => fake()->sentence(),
                'created_at' => now(),
            ]);
        }

        foreach (Article::inRandomOrder()->take(5)->get() as $article) {
            DB::table('reports')->insert([
                'causer' => fake()->randomElement($causerIds),
                'target_type' => 'article',
                'target_id' => (string) $article->id,
                'content' => fake()->sentence(),
                'created_at' => now(),
            ]);
        }

        foreach (Comment::inRandomOrder()->take(5)->get() as $comment) {
            DB::table('reports')->insert([
                'causer' => fake()->randomElement($causerIds),
                'target_type' => 'comment',
                'target_id' => (string) $comment->id,
                'content' => fake()->sentence(),
                'created_at' => now(),
            ]);
        }
    }
}
