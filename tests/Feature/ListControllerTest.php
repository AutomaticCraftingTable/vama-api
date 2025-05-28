<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\Note;
use App\Models\Article;
use App\Models\Comment;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticate($user)
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    protected function createUserWithRole($role)
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_it_returns_all_moderators_with_profiles()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $moderators = User::factory()->count(2)->create(['role' => 'moderator']);
        foreach ($moderators as $moderator) {
            Profile::factory()->create(['user_id' => $moderator->id]);
        }

        User::factory()->create(['role' => 'user']);
        User::factory()->create(['role' => 'superadmin']);

        $response = $this->withToken($token)->getJson('/api/list/moderators');

        $response->assertOk()
                 ->assertJsonCount(2, 'moders')
                 ->assertJsonStructure([
                     'moders' => [
                         [
                             'id',
                             'email',
                             'role',
                             'created_at',
                             'updated_at',
                             'profile' => [
                                 'nickname',
                                 'description',
                                 'logo',
                                 'created_at',
                                 'updated_at',
                             ],
                         ],
                     ],
                 ]);
    }

    public function test_admin_can_see_all_notes()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $causer = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $causer->id]);

        $article = Article::factory()->create();

        Note::factory()->count(3)->create([
            'causer' => $profile->nickname,
            'article_id' => $article->id,
        ]);

        $response = $this->withToken($token)->getJson('/api/list/notes');

        $response->assertOk()
                 ->assertJsonCount(3, 'notes')
                 ->assertJsonStructure([
                     'notes' => [
                         [
                             'id',
                             'content',
                             'causer',
                             'article_id',
                             'created_at',
                             'updated_at',
                             'profile',
                             'article',
                         ],
                     ],
                 ]);
    }


    public function test_user_cannot_access_notes()
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/list/notes');

        $response->assertStatus(403);
    }


    public function test_admin_can_see_reported_articles()
    {
        $admin = $this->createUserWithRole('admin');
        Profile::factory()->create(['user_id' => $admin->id]);
        $token = $this->authenticate($admin);

        $article = Article::factory()->create();

        $reports = Report::factory()->count(2)->create([
            'target_type' => 'article',
            'target_id' => $article->id,
        ]);

        $response = $this->withToken($token)->getJson('/api/list/reports/articles');

        $response->assertOk()
                 ->assertJsonStructure([
                     'articles' => [
                         '*' => [
                             'id',
                             'author' => ['nickname', 'account_id', 'logo', 'followers'],
                             'title',
                             'content',
                             'tags',
                             'likes',
                             'comments',
                             'thumbnail',
                             'banned_at',
                             'created_at',
                             'updated_at',
                         ],
                     ],
                 ]);
    }

    public function test_admin_can_see_reported_profiles()
    {
        $admin = $this->createUserWithRole('admin');
        Profile::factory()->create(['user_id' => $admin->id]);
        $token = $this->authenticate($admin);

        $targetUser = $this->createUserWithRole('user');
        $targetProfile = Profile::factory()->create(['user_id' => $targetUser->id]);

        Report::factory()->count(2)->create([
            'target_type' => 'profile',
            'target_id' => $targetProfile->nickname,
        ]);

        $response = $this->withToken($token)->getJson('/api/list/reports/profiles');

        $response->assertOk()
                 ->assertJsonStructure([
                     'profiles' => [
                         '*' => [
                             'nickname',
                             'account_id',
                             'description',
                             'logo',
                             'followers',
                             'created_at',
                             'updated_at',
                         ],
                     ],
                 ]);
    }

    public function test_admin_can_see_reported_comments()
    {
        $admin = $this->createUserWithRole('admin');
        Profile::factory()->create(['user_id' => $admin->id]);
        $token = $this->authenticate($admin);

        $article = Article::factory()->create();
        $comment = Comment::factory()->create(['article_id' => $article->id]);

        Report::factory()->count(2)->create([
            'target_type' => 'comment',
            'target_id' => $comment->id,
        ]);

        $response = $this->withToken($token)->getJson('/api/list/reports/comments');

        $response->assertOk()
                 ->assertJsonStructure([
                     'comments' => [
                         '*' => [
                             'id',
                             'causer',
                             'article_id',
                             'content',
                             'banned_at',
                             'created_at',
                             'updated_at',
                             'logo',
                             'likes',
                         ],
                     ],
                 ]);
    }
}
