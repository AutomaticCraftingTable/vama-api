<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\LikeReaction;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeReactionTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithArticle(): array
    {
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'nickname' => 'nickname_' . $user->id,
        ]);

        $article = Article::factory()->create([
            'author' => $user->profile->nickname,
        ]);

        return compact('user', 'article');
    }

    public function test_user_can_like_an_article()
    {
        ['user' => $user, 'article' => $article] = $this->createUserWithArticle();

        $response = $this->actingAs($user)->postJson("/api/article/{$article->id}/like");

        $response->assertStatus(201);
        $this->assertDatabaseHas('like_reactions', [
            'causer' => $user->id,
            'article_id' => $article->id,
        ]);
    }

    public function test_user_cannot_like_same_article_twice()
    {
        ['user' => $user, 'article' => $article] = $this->createUserWithArticle();

        $this->actingAs($user)->postJson("/api/article/{$article->id}/like");
        $response = $this->actingAs($user)->postJson("/api/article/{$article->id}/like");

        $response->assertStatus(409);
    }

    public function test_user_can_unlike_article()
    {
        ['user' => $user, 'article' => $article] = $this->createUserWithArticle();

        $this->actingAs($user)->postJson("/api/article/{$article->id}/like");
        $response = $this->actingAs($user)->deleteJson("/api/article/{$article->id}/like");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('like_reactions', [
            'causer' => $user->id,
            'article_id' => $article->id,
        ]);
    }

    public function test_user_cannot_unlike_if_not_liked()
    {
        ['user' => $user, 'article' => $article] = $this->createUserWithArticle();

        $response = $this->actingAs($user)->deleteJson("/api/article/{$article->id}/like");

        $response->assertStatus(404);
    }

    public function test_guest_cannot_like_or_unlike()
    {
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'nickname' => 'nickname_' . $user->id,
        ]);
        $article = Article::factory()->create([
            'author' => $user->profile->nickname,
        ]);

        $likeResponse = $this->postJson("/api/article/{$article->id}/like");
        $unlikeResponse = $this->deleteJson("/api/article/{$article->id}/like");

        $likeResponse->assertStatus(401);
        $unlikeResponse->assertStatus(401);
    }
}
