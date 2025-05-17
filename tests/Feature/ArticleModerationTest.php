<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArticleModerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function createUser(string $role): User
    {
        $user = User::factory()->role($role)->create();
        Profile::factory()->create(['user_id' => $user->id]);
        return $user;
    }


    private function createUserWithProfile(string $role, string $nickname = null): User
    {
        $user = User::factory()->create(['role' => $role])->fresh();

        Profile::factory()->create([
            'user_id' => $user->id,
            'nickname' => $nickname ?? 'nick_' . uniqid(),
        ]);

        return $user;
    }

    private function createArticle(User $author, bool $banned = false): Article
    {
        $profile = $author->profile;
        return Article::factory()->create([
            'author' => $profile->nickname,
            'banned_at' => $banned ? now() : null,
        ]);
    }

    public function test_admin_can_ban_article()
    {
        $admin = $this->createUser('admin');
        $article = $this->createArticle($admin);

        $response = $this->actingAs($admin)->postJson("/api/article/{$article->id}/ban", [
            'reason' => 'Inappropriate content',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($article->fresh()->banned_at);
    }

    public function test_admin_cannot_ban_already_banned_article()
    {
        $admin = $this->createUser('admin');
        $article = $this->createArticle($admin, banned: true);

        $response = $this->actingAs($admin)->postJson("/api/article/{$article->id}/ban", [
            'reason' => 'Try again',
        ]);

        $response->assertStatus(400);
    }

    public function test_ban_fails_without_reason()
    {
        $admin = $this->createUser('admin');
        $article = $this->createArticle($admin);

        $response = $this->actingAs($admin)->postJson("/api/article/{$article->id}/ban");

        $response->assertStatus(422);
        $this->assertNull($article->fresh()->banned_at);
    }

    public function test_user_cannot_ban_article()
    {
        $user = $this->createUser('user');
        $article = $this->createArticle($user);

        $response = $this->actingAs($user)->postJson("/api/article/{$article->id}/ban", [
            'reason' => 'I want it banned',
        ]);

        $response->assertStatus(403);
        $this->assertNull($article->fresh()->banned_at);
    }

    public function test_admin_can_unban_article()
    {
        $admin = $this->createUser('admin');
        $article = $this->createArticle($admin, banned: true);

        DB::table('bans')->insert([
            'causer' => $admin->id,
            'target_type' => 'article',
            'target_id' => $article->id,
            'content' => 'Test ban',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/article/{$article->id}/ban");

        $response->assertStatus(200);
        $this->assertNull($article->fresh()->banned_at);
    }

    public function test_unban_fails_if_article_is_not_banned()
    {
        $admin = $this->createUser('admin');
        $article = $this->createArticle($admin, banned: false);

        $response = $this->actingAs($admin)->deleteJson("/api/article/{$article->id}/ban");

        $response->assertStatus(400);
        $this->assertNull($article->fresh()->banned_at);
    }

    public function test_user_cannot_unban_article()
    {
        $user = $this->createUser('user');
        $article = $this->createArticle($user, banned: true);

        $response = $this->actingAs($user)->deleteJson("/api/article/{$article->id}/ban");

        $response->assertStatus(403);
        $this->assertNotNull($article->fresh()->banned_at);
    }

    public function test_ban_entry_is_deleted_on_unban()
    {
        $admin = $this->createUser('admin');
        $article = $this->createArticle($admin, banned: true);

        DB::table('bans')->insert([
            'causer' => $admin->id,
            'target_type' => 'article',
            'target_id' => $article->id,
            'content' => 'Violation',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->deleteJson("/api/article/{$article->id}/ban");

        $this->assertDatabaseMissing('bans', [
            'target_type' => 'article',
            'target_id' => $article->id,
        ]);
    }

    public function test_admin_can_delete_article()
    {
        $admin = $this->createUserWithProfile('admin');
        $article = Article::factory()->create();

        $response = $this->actingAs($admin)->deleteJson("/api/article/{$article->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    public function test_author_can_delete_own_article()
    {
        $user = $this->createUserWithProfile('user', 'author_nick');
        $article = Article::factory()->create(['author' => 'author_nick']);

        $response = $this->actingAs($user)->deleteJson("/api/article/{$article->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    public function test_user_cannot_delete_others_article()
    {
        $user = $this->createUserWithProfile('user', 'different_nick');

        Profile::factory()->create(['nickname' => 'some_other_nick']);

        $article = Article::factory()->create(['author' => 'some_other_nick']);

        $response = $this->actingAs($user)->deleteJson("/api/article/{$article->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('articles', ['id' => $article->id]);
    }
}
