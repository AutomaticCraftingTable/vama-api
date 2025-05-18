<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\Report;
use App\Models\Article;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_article()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['account_id' => $user->id]);
        $article = Article::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/article/{$article->id}/report", [
            'content' => 'This is inappropriate',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Report submitted successfully.']);

        $this->assertDatabaseHas('reports', [
            'target_type' => 'article',
            'target_id' => $article->id,
            'causer' => $user->id,
        ]);
    }

    public function test_user_can_report_comment()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['account_id' => $user->id]);
        $comment = Comment::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/comment/{$comment->id}/report", [
            'content' => 'This is a spam comment',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Report submitted successfully.']);

        $this->assertDatabaseHas('reports', [
            'target_type' => 'comment',
            'target_id' => $comment->id,
            'causer' => $user->id,
        ]);
    }

    public function test_user_can_report_profile()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['account_id' => $user->id]);
        $targetProfile = Profile::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/profile/{$targetProfile->nickname}/report", [
            'content' => 'Fake profile',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Report submitted successfully.']);

        $this->assertDatabaseHas('reports', [
            'target_type' => 'profile',
            'target_id' => $targetProfile->id,
            'causer' => $user->id,
        ]);
    }

    public function test_report_requires_content()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/article/{$article->id}/report", []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['content']);
    }

    public function test_admin_can_delete_article_report()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $article = Article::factory()->create();

        Report::factory()->create([
            'target_type' => 'article',
            'target_id' => $article->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/article/{$article->id}/report");

        $response->assertOk()
                 ->assertJson(['message' => 'Report(s) deleted successfully.']);

        $this->assertDatabaseMissing('reports', [
            'target_type' => 'article',
            'target_id' => $article->id,
        ]);
    }

    public function test_non_admin_cannot_delete_reports()
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        Report::factory()->create([
            'target_type' => 'article',
            'target_id' => $article->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/article/{$article->id}/report");

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Unauthorized.']);

        $this->assertDatabaseHas('reports', [
            'target_type' => 'article',
            'target_id' => $article->id,
        ]);
    }
}
