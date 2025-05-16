<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Comment;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CommentModerationTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function createComment(User $user, bool $banned = false): Comment
    {
        // Create a profile with nickname matching the user name or a unique one
        $profile = Profile::factory()->create();

        // Create an article written by that profile
        $article = Article::factory()->create([
            'author' => $profile->nickname,
        ]);

        // Create the comment without 'author' key to avoid SQL error
        return Comment::factory()->create([
            'article_id' => $article->id,
            'causer' => $user->id,
            'banned_at' => $banned ? now() : null,
        ]);
    }

    public function test_admin_can_ban_comment()
    {
        $admin = $this->createUser('admin');
        $comment = $this->createComment($admin);

        $response = $this->actingAs($admin)->postJson("/api/comment/{$comment->id}/ban", [
            'reason' => 'Offensive content',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($comment->fresh()->banned_at);
    }

    public function test_admin_cannot_ban_already_banned_comment()
    {
        $admin = $this->createUser('admin');
        $comment = $this->createComment($admin, true);

        $response = $this->actingAs($admin)->postJson("/api/comment/{$comment->id}/ban", [
            'reason' => 'Duplicate ban attempt',
        ]);

        $response->assertStatus(400);
    }

    public function test_ban_fails_without_reason()
    {
        $admin = $this->createUser('admin');
        $comment = $this->createComment($admin);

        $response = $this->actingAs($admin)->postJson("/api/comment/{$comment->id}/ban");

        $response->assertStatus(422);
        $this->assertNull($comment->fresh()->banned_at);
    }

    public function test_user_cannot_ban_comment()
    {
        $user = $this->createUser('user');
        $comment = $this->createComment($user);

        $response = $this->actingAs($user)->postJson("/api/comment/{$comment->id}/ban", [
            'reason' => 'Just cause',
        ]);

        $response->assertStatus(403);
        $this->assertNull($comment->fresh()->banned_at);
    }

    public function test_admin_can_unban_comment()
    {
        $admin = $this->createUser('admin');
        $comment = $this->createComment($admin, true);

        DB::table('bans')->insert([
            'causer' => $admin->id,
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'content' => 'Initial ban reason',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/comment/{$comment->id}/ban");

        $response->assertStatus(200);
        $this->assertNull($comment->fresh()->banned_at);
    }

    public function test_unban_fails_if_comment_not_banned()
    {
        $admin = $this->createUser('admin');
        $comment = $this->createComment($admin, false);

        $response = $this->actingAs($admin)->deleteJson("/api/comment/{$comment->id}/ban");

        $response->assertStatus(400);
    }

    public function test_user_cannot_unban_comment()
    {
        $user = $this->createUser('user');
        $comment = $this->createComment($user, true);

        $response = $this->actingAs($user)->deleteJson("/api/comment/{$comment->id}/ban");

        $response->assertStatus(403);
        $this->assertNotNull($comment->fresh()->banned_at);
    }

    public function test_ban_entry_is_deleted_on_unban()
    {
        $admin = $this->createUser('admin');
        $comment = $this->createComment($admin, true);

        DB::table('bans')->insert([
            'causer' => $admin->id,
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'content' => 'Bad comment',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)->deleteJson("/api/comment/{$comment->id}/ban");

        $this->assertDatabaseMissing('bans', [
            'target_type' => 'comment',
            'target_id' => $comment->id,
        ]);
    }

    public function test_admin_can_delete_comment()
    {
        $admin = $this->createUser('admin');
        $comment = $this->createComment($admin);

        $response = $this->actingAs($admin)->deleteJson("/api/comment/{$comment->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_author_can_delete_own_comment()
    {
        $user = $this->createUser('user');
        $comment = $this->createComment($user);

        $response = $this->actingAs($user)->deleteJson("/api/comment/{$comment->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_others_comment()
    {
        $user1 = $this->createUser('user');
        $user2 = $this->createUser('user');
        $comment = $this->createComment($user2);

        $response = $this->actingAs($user1)->deleteJson("/api/comment/{$comment->id}");
        $response->assertStatus(403);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }
}
