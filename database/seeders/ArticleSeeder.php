<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Profile;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = Profile::all();

        if ($profiles->isEmpty()) {
            $this->command->error('No profiles found. Seed users and profiles first.');
            return;
        }

        Article::factory()
            ->count(25)
            ->make()
            ->each(function ($article) use ($profiles) {
                $article->author = $profiles->random()->nickname;
                $article->save();
            });
    }
}
