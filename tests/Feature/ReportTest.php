<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Profile;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private static int $emailIncrement = 1;

    protected $authorProfile;
    protected $article;

    protected function setUp(): void
    {
        parent::setUp();

        $author = $this->createUserWithRole('user');
        $this->authorProfile = Profile::factory()->create(['user_id' => $author->id]);
        $this->article = Article::factory()->create([
            'author' => $this->authorProfile->nickname,
        ]);
    }

    private function createUserWithRole(string $role): User
    {
        return User::create([
            'email' => "{$role}" . self::$emailIncrement++ . "@example.com",
            'password' => Hash::make('password'),
            'role' => $role,
            'email_verified_at' => now(),
        ]);
    }

    private function authenticate(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    /** Report Articles **/

    public function test_user_can_report_article()
    {
        $user = $this->createUserWithRole('user');
        Profile::factory()->create(['user_id' => $user->id]);
        $token = $this->authenticate($user);

        $response = $this->withToken($token)->postJson("/api/article/{$this->article->id}/report", [
            'content' => 'Inappropriate content',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Article reported successfully.']);

        $this->assertDatabaseHas('reports', [
            'causer' => $user->id,
            'target_type' => 'article',
            'target_id' => $this->article->id,
        ]);
    }

    /** Report Comments **/

    public function test_user_can_report_comment()
    {
        $user = $this->createUserWithRole('user');
        Profile::factory()->create(['user_id' => $user->id]);
        $token = $this->authenticate($user);
        $comment = Comment::factory()->create(['article_id' => $this->article->id]);

        $response = $this->withToken($token)->postJson("/api/comment/{$comment->id}/report", [
            'content' => 'Spam or offensive',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Comment reported successfully.']);

        $this->assertDatabaseHas('reports', [
            'causer' => $user->id,
            'target_type' => 'comment',
            'target_id' => $comment->id,
        ]);
    }

    /** Report Profiles **/

    public function test_user_can_report_profile()
    {
        $user = $this->createUserWithRole('user');
        Profile::factory()->create(['user_id' => $user->id]);
        $token = $this->authenticate($user);

        $targetUser = $this->createUserWithRole('user');
        $targetProfile = Profile::factory()->create([
            'user_id' => $targetUser->id,
        ]);

        $response = $this->withToken($token)->postJson("/api/profile/{$targetProfile->nickname}/report", [
            'content' => 'Suspicious activity',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Profile reported successfully.']);

        $this->assertDatabaseHas('reports', [
    'causer' => $user->id,
    'target_type' => 'profile',
    'target_id' => $targetProfile->nickname,
        ]);
    }

    /** Validation **/

    public function test_report_validation_fails_when_content_missing()
    {
        $user = $this->createUserWithRole('user');
        Profile::factory()->create(['user_id' => $user->id]);
        $token = $this->authenticate($user);

        $response = $this->withToken($token)->postJson("/api/article/{$this->article->id}/report", []);

        $response->assertStatus(422);
    }

    public function test_admin_can_delete_reports()
    {
        $admin = $this->createUserWithRole('admin');
        Profile::factory()->create(['user_id' => $admin->id]);
        $token = $this->authenticate($admin);

        $targetUser = $this->createUserWithRole('user');
        $profile = Profile::factory()->create(['user_id' => $targetUser->id]);

        Report::factory()->count(3)->create([
            'target_type' => 'profile',
            'target_id' => $profile->nickname,
        ]);

        $response = $this->withToken($token)->deleteJson("/api/profile/{$profile->nickname}/report");

        $response->assertOk()
                 ->assertJsonFragment([
                     'message' => "3 report(s) on profile #{$profile->nickname} deleted successfully.",
                 ]);
    }

    public function test_non_admin_cannot_delete_reports()
    {
        $user = $this->createUserWithRole('user');
        Profile::factory()->create(['user_id' => $user->id]);
        $token = $this->authenticate($user);

        $targetUser = $this->createUserWithRole('user');
        $profile = Profile::factory()->create(['user_id' => $targetUser->id]);

        Report::factory()->create([
            'target_type' => 'profile',
            'target_id' => $profile->nickname,
        ]);

        $response = $this->withToken($token)->deleteJson("/api/profile/{$profile->nickname}/report");

        $response->assertStatus(403);
    }
}
