<?php

namespace Database\Factories; // ✅ Important

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Comment;
use App\Models\Profile;
use App\Models\Article;

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
