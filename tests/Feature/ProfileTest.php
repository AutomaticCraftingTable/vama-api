<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\Article;
use App\Models\Comment;
use App\Models\LikeReaction;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(User $user): array
    {
        $token = $user->createToken('test-token')->plainTextToken;
        return ['Authorization' => 'Bearer ' . $token];
    }

    private function createUser(): User
    {
        return User::factory()->create();
    }

    public function test_user_can_create_profile()
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/profile', [
            'nickname' => 'john_doe',
            'description' => 'Just a test user.',
            'logo' => 'logo.png',
        ], $this->authHeader($user));

        $response->assertStatus(201)
                 ->assertJsonStructure(['profile' => ['nickname', 'description', 'logo']]);

        $this->assertDatabaseHas('profiles', [
            'nickname' => 'john_doe',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_create_multiple_profiles()
    {
        $user = $this->createUser();
        Profile::factory()->for($user)->create(['nickname' => 'existing']);

        $response = $this->postJson('/api/profile', [
            'nickname' => 'new_nick',
        ], $this->authHeader($user));

        $response->assertStatus(409)
                 ->assertJson(['message' => 'Profile already exists.']);
    }

    public function test_user_can_update_profile()
    {
        $user = $this->createUser();
        Profile::factory()->for($user)->create([
            'nickname' => 'original',
            'description' => 'Old description',
        ]);

        $response = $this->putJson('/api/profile', [
            'description' => 'Updated!',
        ], $this->authHeader($user));

        $response->assertStatus(200)
                 ->assertJsonFragment(['description' => 'Updated!']);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'description' => 'Updated!',
        ]);
    }

    public function test_user_cannot_update_profile_if_none_exists()
    {
        $user = $this->createUser();

        $response = $this->putJson('/api/profile', [
            'description' => 'Trying to update',
        ], $this->authHeader($user));

        $response->assertStatus(404);
    }

    public function test_user_can_delete_profile()
    {
        $user = $this->createUser();
        Profile::factory()->for($user)->create([
            'nickname' => 'to_be_deleted',
        ]);

        $response = $this->deleteJson('/api/profile', [], $this->authHeader($user));

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Profile deleted.']);

        $this->assertDatabaseMissing('profiles', [
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_delete_profile_if_none_exists()
    {
        $user = $this->createUser();

        $response = $this->deleteJson('/api/profile', [], $this->authHeader($user));

        $response->assertStatus(404);
    }

    public function test_authenticated_user_can_view_own_profile()
    {
        $user = $this->createUser();
        $profile = Profile::factory()->for($user)->create();

        $response = $this->getJson('/api/profile', $this->authHeader($user));

        $response->assertOk()
            ->assertJson([
                'nickname' => $profile->nickname,
                'account_id' => $user->id,
                'description' => $profile->description,
                'logo' => $profile->logo,
                'followers' => 0,
                'role' => $user->role,
                'state' => 'hasProfile',
                'profile' => [
                    'nickname' => $profile->nickname,
                    'account_id' => $user->id,
                ],
            ]);
    }

    public function test_user_can_view_another_profile()
    {
        $user = $this->createUser();
        $targetUser = $this->createUser();
        $targetProfile = Profile::factory()->for($targetUser)->create(['nickname' => 'target_user']);

        Subscription::factory()->create([
            'causer' => Profile::factory()->create()->nickname,
            'author' => $targetProfile->nickname,
        ]);

        $response = $this->getJson('/api/profile/' . $targetProfile->nickname, $this->authHeader($user));

        $response->assertOk()
            ->assertJson([
                'nickname' => $targetProfile->nickname,
                'account_id' => $targetUser->id,
                'followers' => 1,
            ]);
    }

    public function test_profile_view_includes_articles_likes_and_comments()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;
        $profile = Profile::factory()->for($user)->create();

        $article = Article::factory()->create([
            'author' => $profile->nickname,
        ]);

        LikeReaction::factory()->create([
            'article_id' => $article->id,
            'causer' => $user->id,
        ]);

        $commentingUser = User::factory()->create();
        $commentProfile = Profile::factory()->for($commentingUser)->create();

        Comment::factory()->create([
        'article_id' => $article->id,
        'causer' => $commentingUser->id,
        ]);

        $response = $this->withToken($token)->getJson('/api/profile');

        $response->assertOk()
            ->assertJsonStructure([
                'articles' => [
                    [
                        'id',
                        'title',
                        'content',
                        'tags',
                        'likes',
                        'comments' => [
                            [
                                'id',
                                'causer',
                                'article_id',
                                'content',
                                'created_at',
                                'updated_at',
                                'logo',
                            ],
                        ],
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_guest_cannot_access_profile()
    {
        $response = $this->getJson('/api/profile');
        $response->assertUnauthorized();
    }
}
