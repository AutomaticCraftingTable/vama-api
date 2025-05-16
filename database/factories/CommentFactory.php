<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Article;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->for($user)->create();

        $article = Article::factory()->create([
            'author' => $profile->nickname,
        ]);

        return [
            'article_id' => $article->id,
            'causer' => $user->id,
            'content' => $this->faker->paragraph,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
