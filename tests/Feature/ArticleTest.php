<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
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

    public function test_authenticated_user_can_create_article_with_valid_data()
    {
        $user = $this->createUserWithProfile();
        $nickname = $user->profile->nickname;

        $payload = [
            'title' => 'Test Article Title',
            'content' => 'This is the test content of the article.',
            'tags' => 'tag1,tag2',
        ];

        $response = $this->actingAs($user)->postJson("/api/article/{$nickname}", $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('articles', [
            'author' => $nickname,
            'title' => $payload['title'],
            'content' => $payload['content'],
            'tags' => $payload['tags'],
        ]);
    }

    public function test_cannot_create_article_with_missing_title()
    {
        $user = $this->createUserWithProfile();
        $nickname = $user->profile->nickname;

        $payload = [
            'content' => 'Content without title',
            'tags' => 'tag1',
        ];

        $response = $this->actingAs($user)->postJson("/api/article/{$nickname}", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('title');
    }

    public function test_cannot_create_article_with_missing_content()
    {
        $user = $this->createUserWithProfile();
        $nickname = $user->profile->nickname;

        $payload = [
            'title' => 'Title without content',
        ];

        $response = $this->actingAs($user)->postJson("/api/article/{$nickname}", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('content');
    }

    public function test_unauthenticated_user_cannot_create_article()
    {
        $profile = Profile::factory()->create();
        $nickname = $profile->nickname;

        $payload = [
            'title' => 'Unauthorized',
            'content' => 'Should not create',
        ];

        $response = $this->postJson("/api/article/{$nickname}", $payload);

        $response->assertStatus(401);
    }

    public function test_creating_article_with_invalid_profile_nickname_returns_404()
    {
        $user = $this->createUserWithProfile();
        $invalidNickname = 'nonexistent_nickname';

        $payload = [
            'title' => 'Title',
            'content' => 'Content',
        ];

        $response = $this->actingAs($user)->postJson("/api/article/{$invalidNickname}", $payload);

        $response->assertStatus(404);
    }
}
