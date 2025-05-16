<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Article;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        $profile = Profile::factory()->create();
        $article = Article::factory()->create([
            'author' => $profile->nickname,
        ]);

        return [
            'content' => $this->faker->sentence,
            'causer' => $profile->nickname,
            'article_id' => $article->id,
        ];
    }
}
