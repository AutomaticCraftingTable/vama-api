<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Comment;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private function createUserWithProfile(): User
    {
        $user = User::factory()->create();
        Profile::factory()->create([
            'user_id' => $user->id,
            'nickname' => 'nickname_' . $user->id,
        ]);
        return $user;
    }

    public function test_authenticated_user_can_create_comment_with_valid_data()
    {
        $user = $this->createUserWithProfile();

        $article = Article::factory()->create([
            'author' => $user->profile->nickname,
        ]);

        $payload = [
            'content' => 'This is a test comment.',
        ];

        $response = $this->actingAs($user)->postJson("/api/article/{$article->id}/comment", $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'content' => $payload['content'],
                     'causer' => $user->id,
                     'article_id' => $article->id,
                 ]);

        $this->assertDatabaseHas('comments', [
            'causer' => $user->id,
            'article_id' => $article->id,
            'content' => $payload['content'],
        ]);
    }

    public function test_create_comment_requires_content()
    {
        $user = $this->createUserWithProfile();
        $article = Article::factory()->create(['author' => $user->profile->nickname]);

        $payload = [];

        $response = $this->actingAs($user)->postJson("/api/article/{$article->id}/comment", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('content');
    }

    public function test_create_comment_returns_404_if_article_not_found()
    {
        $user = $this->createUserWithProfile();

        $invalidArticleId = 999999;

        $payload = ['content' => 'Some comment'];

        $response = $this->actingAs($user)->postJson("/api/article/{$invalidArticleId}/comment", $payload);

        $response->assertStatus(404);
        $response->assertJson(['message' => 'Article not found']);
    }

    public function test_guest_cannot_create_comment()
    {
        $user = $this->createUserWithProfile();
        $article = Article::factory()->create(['author' => $user->profile->nickname]);

        $payload = ['content' => 'Guest comment'];

        $response = $this->postJson("/api/article/{$article->id}/comment", $payload);

        $response->assertStatus(401);
    }
}
