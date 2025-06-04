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

    public function test_it_returns_all_moderators_with_notes()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test-token')->plainTextToken;

        $moderators = User::factory()->count(2)->create(['role' => 'moderator']);
        foreach ($moderators as $moderator) {
            $profile = Profile::factory()->create(['user_id' => $moderator->id]);

            Note::factory()->count(2)->create([
                'causer' => $profile->nickname,
                'article_id' => Article::factory()->create()->id,
            ]);
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
                             'notes' => [
                                 [
                                     'id',
                                     'content',
                                     'causer',
                                     'article_id',
                                     'created_at',
                                     'updated_at',
                                 ],
                             ],
                         ],
                     ],
                 ]);


        $data = $response->json('moders');
        foreach ($data as $moderator) {
            $this->assertCount(2, $moderator['notes']);
        }
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

    public function test_reported_articles_returns_correct_structure_and_data()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $token = $adminUser->createToken('TestToken')->plainTextToken;

        $authorUser = User::factory()->create();
        $reporterUser = User::factory()->create();

        $authorProfile = Profile::factory()->create([
            'user_id' => $authorUser->id,
            'nickname' => 'author_nick',
            'logo' => 'logo.png',
        ]);

        $article = Article::factory()->create([
            'author' => $authorProfile->nickname,
            'title' => 'Sample Article',
            'content' => 'Content of article',
            'tags' => 'tag1,tag2',
        ]);

        Report::create([
            'causer' => $reporterUser->id,
            'target_type' => 'article',  // use the expected string here!
            'target_id' => (string) $article->id,
            'content' => 'Inappropriate content',
        ]);

        $this->withoutMiddleware(\App\Http\Middleware\CanAccessContent::class);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/list/reports/articles');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'articles' => [
                '*' => [
                    'id',
                    'author' => [
                        'nickname',
                        'account_id',
                        'logo',
                        'followers',
                    ],
                    'title',
                    'content',
                    'tags',
                    'likes',
                    'reporter' => [
                        'id',
                        'email',
                        'role',
                    ],
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


    public function test_admin_can_list_profiles_with_activities()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('TestToken')->plainTextToken;

        $profile = \App\Models\Profile::factory()->create([
            'nickname' => 'test_profile',
            'description' => 'Test description',
            'logo' => 'logo.png',
            'user_id' => $admin->id,
        ]);

        activity('profiles')
            ->causedBy($admin)
            ->performedOn($profile)
            ->log('Test activity on profile');

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/api/list/profiles');

        $responseData = $response->json();
        $this->assertNotEmpty($responseData['profiles'], 'No profiles returned in response.');

        $response->assertJsonStructure([
            'state',
            'profiles' => [
                '*' => [
                    'nickname',
                    'account_id',
                    'description',
                    'logo',
                    'followers',
                    'created_at',
                    'updated_at',
                    'activities' => [
                        '*' => [
                            'id',
                            'log_name',
                            'description',
                            'subject_id',
                            'subject_type',
                            'causer_id',
                            'causer_type',
                            'properties',
                            'event',
                            'created_at',
                            'updated_at',
                            'status',
                        ],
                    ],
                ],
            ],
        ]);
    }




    public function test_non_admin_cannot_list_profiles()
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('TestToken')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/api/list/profiles');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_profile_list()
    {
        $response = $this->getJson('/api/list/profiles');

        $response->assertStatus(401);
    }
}
