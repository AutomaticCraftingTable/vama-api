<?php

namespace Database\Factories;

use App\Models\LikeReaction;
use App\Models\User;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

class LikeReactionFactory extends Factory
{
    protected $model = LikeReaction::class;

    public function definition(): array
    {
        return [
            'causer' => User::inRandomOrder()->first()->id,
            'article_id' => Article::inRandomOrder()->first()->id,
        ];
    }
}
