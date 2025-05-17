<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'author' => Profile::inRandomOrder()->first()->nickname ?? 'guest',
            'title' => $this->faker->sentence,
            'content' => $this->faker->paragraph(6),
            'tags' => implode(',', $this->faker->words(3)),
            'banned_at' => null,
        ];
    }
}
