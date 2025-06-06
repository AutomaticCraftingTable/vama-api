<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Note;
use App\Models\Article;
use App\Models\Profile;

class NoteSeeder extends Seeder
{
    public function run(): void
    {
        $articles = Article::all();
        $profiles = Profile::all();

        if ($articles->isEmpty() || $profiles->isEmpty()) {
            $this->command->warn('Seed articles and profiles first.');
            return;
        }

        for ($i = 0; $i < 20; $i++) {
            Note::create([
                'content' => fake()->sentence(),
                'causer' => $profiles->random()->nickname,
                'article_id' => $articles->random()->id,
            ]);
        }
    }
}
