<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Subscription;
use App\Models\Note;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Laravel\Sanctum\Sanctum;

class LogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Profile $profile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->profile = Profile::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);
    }

    public function test_article_creation_logs_activity()
    {
        $payload = [
            'title' => 'Test Article',
            'content' => 'This is test content.',
            'tags' => 'test,article',
        ];

        $response = $this->postJson("/api/article/{$this->profile->nickname}", $payload);
        $response->assertStatus(201);

        $article = Article::first();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Article::class,
            'subject_id' => $article->id,
            'description' => 'Article created',
            'log_name' => 'articles',
        ]);

        $activity = Activity::where('subject_id', $article->id)
            ->where('description', 'Article created')
            ->first();

        $this->assertEquals($this->user->id, $activity->causer_id);
    }

    public function test_article_ban_logs_activity()
    {
        $article = Article::factory()->create();

        $response = $this->postJson("/api/article/{$article->id}/ban", [
            'reason' => 'Inappropriate content',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => 'Article has been banned.']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Article::class,
            'subject_id' => $article->id,
            'description' => 'Article banned',
            'log_name' => 'articles',
        ]);
    }

    public function test_article_unban_logs_activity()
    {
        $article = Article::factory()->create([
            'banned_at' => now(),
        ]);

        $response = $this->deleteJson("/api/article/{$article->id}/ban");

        $response->assertStatus(200)
            ->assertJson(['success' => 'Article has been unbanned.']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Article::class,
            'subject_id' => $article->id,
            'description' => 'Article unbanned',
            'log_name' => 'articles',
        ]);
    }

    public function test_article_delete_logs_activity()
    {
        $article = Article::factory()->create([
            'author' => $this->profile->nickname,
        ]);

        $response = $this->deleteJson("/api/article/{$article->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => 'Article deleted.']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Article::class,
            'subject_id' => $article->id,
            'description' => 'Article deleted',
            'log_name' => 'articles',
        ]);
    }

    public function test_create_comment_logs_activity()
    {
        $article = Article::factory()->create();

        $response = $this->postJson("/api/article/{$article->id}/comment", [
            'content' => 'This is a test comment.',
        ]);

        $response->assertStatus(201);

        $comment = Comment::first();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Comment::class,
            'subject_id' => $comment->id,
            'description' => 'Comment created',
            'log_name' => 'comments',
        ]);
    }

    public function test_ban_comment_logs_activity()
    {
        $comment = Comment::factory()->create();

        $response = $this->postJson("/api/comment/{$comment->id}/ban", [
            'reason' => 'Spam',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Comment::class,
            'subject_id' => $comment->id,
            'description' => 'Comment banned',
            'log_name' => 'comments',
        ]);
    }

    public function test_unban_comment_logs_activity()
    {
        $comment = Comment::factory()->create([
            'banned_at' => now(),
        ]);

        $response = $this->deleteJson("/api/comment/{$comment->id}/ban");

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Comment::class,
            'subject_id' => $comment->id,
            'description' => 'Comment unbanned',
            'log_name' => 'comments',
        ]);
    }

    public function test_delete_comment_logs_activity()
    {
        $comment = Comment::factory()->create([
            'causer' => $this->user->id,
        ]);

        $response = $this->deleteJson("/api/comment/{$comment->id}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Comment::class,
            'subject_id' => $comment->id,
            'description' => 'Comment deleted',
            'log_name' => 'comments',
        ]);
    }

    public function test_like_article_logs_activity()
    {
        $article = Article::factory()->create();

        $response = $this->postJson("/api/article/{$article->id}/like");

        $response->assertStatus(201)
            ->assertJson(['message' => 'Article liked successfully']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Article::class,
            'subject_id' => $article->id,
            'description' => 'Article liked',
            'log_name' => 'likes',
        ]);
    }

    public function test_unlike_article_logs_activity()
    {
        $article = Article::factory()->create();

        $this->postJson("/api/article/{$article->id}/like");

        $response = $this->deleteJson("/api/article/{$article->id}/like");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Like removed successfully']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Article::class,
            'subject_id' => $article->id,
            'description' => 'Article unliked',
            'log_name' => 'likes',
        ]);
    }


    public function test_create_note_logs_activity()
    {
        $article = Article::factory()->create();

        $response = $this->postJson("/api/article/{$article->id}/note", [
            'content' => 'This is a note on an article.',
        ]);

        $response->assertStatus(201);

        $note = Note::first();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Note::class,
            'subject_id' => $note->id,
            'description' => 'Note created',
            'log_name' => 'notes',
        ]);
    }

    public function test_delete_note_logs_activity()
    {
        $note = Note::factory()->create([
            'causer' => $this->profile->nickname,
        ]);

        $response = $this->deleteJson("/api/note/{$note->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Note deleted']);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Note::class,
            'subject_id' => $note->id,
            'description' => 'Note deleted',
            'log_name' => 'notes',
        ]);
    }


    public function test_profile_update_logs_activity()
    {
        $response = $this->putJson('/api/profile', [
            'description' => 'Updated description.',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Profile updated',
            'log_name' => 'profiles',
        ]);
    }



    public function test_profile_deletion_logs_activity()
    {
        $response = $this->deleteJson('/api/profile');

        $response->assertStatus(200);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Profile deleted',
            'log_name' => 'profiles',
        ]);
    }


    public function test_subscription_logs_activity()
    {
        $author = Profile::factory()->create(['nickname' => 'target']);

        $response = $this->postJson("/api/profile/{$author->nickname}/subscribe");

        $response->assertStatus(201);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'Subscribed to profile',
            'log_name' => 'subscriptions',
        ]);
    }

    public function test_report_article_logs_activity()
    {
        $article = Article::factory()->create();

        $response = $this->postJson("/api/article/{$article->id}/report", [
            'content' => 'Inappropriate content.',
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Article reported successfully.']);

        $report = Report::first();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Report::class,
            'subject_id' => $report->id,
            'description' => 'Article reported',
            'log_name' => 'reports',
        ]);
    }

    public function test_report_comment_logs_activity()
    {
        $comment = Comment::factory()->create();

        $response = $this->postJson("/api/comment/{$comment->id}/report", [
            'content' => 'Spammy content',
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Comment reported successfully.']);

        $report = Report::first();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Report::class,
            'subject_id' => $report->id,
            'description' => 'Comment reported',
            'log_name' => 'reports',
        ]);
    }

    public function test_report_profile_logs_activity()
    {
        $reportedProfile = Profile::factory()->create();

        $response = $this->postJson("/api/profile/{$reportedProfile->nickname}/report", [
            'content' => 'Fake profile',
        ]);

        $response->assertStatus(201)
            ->assertJson(['message' => 'Profile reported successfully.']);

        $report = Report::first();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Report::class,
            'subject_id' => $report->id,
            'description' => 'Profile reported',
            'log_name' => 'reports',
        ]);
    }

    public function test_delete_reports_logs_activity()
    {
        $targetProfile = Profile::factory()->create();

        Report::factory()->count(2)->create([
            'target_type' => 'profile',
            'target_id' => $targetProfile->nickname,
        ]);

        $response = $this->deleteJson("/api/profile/{$targetProfile->nickname}/report");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'message' => "2 report(s) on profile #{$targetProfile->nickname} deleted successfully.",
            ]);

        $this->assertDatabaseHas('activity_log', [
            'description' => "Deleted 2 report(s) on profile #{$targetProfile->nickname}",
            'log_name' => 'reports',
            'causer_id' => $this->user->id,
        ]);
    }

    public function test_delete_user_logs_activity()
    {
        $targetUser = User::factory()->create(['role' => 'user']);
        $admin = $this->user;

        $response = $this->deleteJson("/api/account/{$targetUser->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Account deleted successfully.']);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'User account deleted',
            'subject_type' => User::class,
            'subject_id' => $targetUser->id,
            'causer_id' => $admin->id,
            'log_name' => 'users',
        ]);
    }


    public function test_ban_user_logs_activity()
    {
        $targetUser = User::factory()->create(['role' => 'user']);
        $response = $this->postJson("/api/account/{$targetUser->id}/ban", [
    'reason' => 'Violation of terms.',
]);

        $response->assertStatus(200)
            ->assertJson(['success' => 'User has been banned successfully.']);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'User banned',
            'subject_type' => User::class,
            'subject_id' => $targetUser->id,
            'causer_id' => $this->user->id,
            'log_name' => 'users',
        ]);
    }

    public function test_unban_user_logs_activity()
    {
        $targetUser = User::factory()->create([
            'role' => 'user',
            'banned_at' => now(),
        ]);

        $response = $this->deleteJson("/api/account/{$targetUser->id}/ban", [
        'reason' => 'Ban lifted after review.',
        ]);


        $response->assertStatus(200)
            ->assertJson(['success' => 'User has been unbanned successfully.']);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'User unbanned',
            'subject_type' => User::class,
            'subject_id' => $targetUser->id,
            'causer_id' => $this->user->id,
            'log_name' => 'users',
        ]);
    }

    public function test_change_user_role_logs_activity()
    {
        $targetUser = User::factory()->create(['role' => 'user']);

        $response = $this->postJson("/api/account/{$targetUser->id}/role", [
        'role' => 'moderator',
]);


        $response->assertStatus(200)
            ->assertJson(['message' => 'Role changed successfully.']);

        $this->assertDatabaseHas('activity_log', [
            'description' => 'User role changed',
            'subject_type' => User::class,
            'subject_id' => $targetUser->id,
            'causer_id' => $this->user->id,
            'log_name' => 'users',
        ]);
    }
}
