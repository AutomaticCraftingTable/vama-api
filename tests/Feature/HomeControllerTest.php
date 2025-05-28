<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\Article;
use App\Models\Comment;
use App\Models\LikeReaction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;


    public function test_home_returns_all_articles_sorted_by_title()
    {
        $authorUser = User::factory()->create();
        $authorProfile = Profile::factory()->create(['user_id' => $authorUser->id, 'nickname' => 'author']);

        $article1 = Article::factory()->create(['title' => 'Banana', 'author' => $authorProfile->nickname]);
        $article2 = Article::factory()->create(['title' => 'Apple', 'author' => $authorProfile->nickname]);
        $article3 = Article::factory()->create(['title' => 'Zebra', 'author' => $authorProfile->nickname]);

        $response = $this->getJson('/api/home');

        $response->assertOk();

        $titles = array_column($response->json('articles'), 'title');

        $this->assertEquals(['Apple', 'Banana', 'Zebra'], $titles);
    }

    public function test_home_returns_correct_article_structure()
    {
        $authorUser = User::factory()->create();
        $authorProfile = Profile::factory()->create(['user_id' => $authorUser->id, 'nickname' => 'author']);

        Article::factory()->create([
            'author' => $authorProfile->nickname,
            'title' => 'Sample Article',
        ]);

        $response = $this->getJson('/api/home');

        $response->assertOk()
            ->assertJsonStructure([
                'state',
                'articles' => [
                    [
                        'id',
                        'author' => [
                            'nickname',
                            'account_id',
                            'description',
                            'logo',
                            'followers',
                            'created_at',
                            'updated_at',
                        ],
                        'title',
                        'content',
                        'tags',
                        'likes',
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
                            ],
                        ],
                        'thumbnail',
                        'banned_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_home_returns_empty_articles_when_none_exist()
    {
        $response = $this->getJson('/api/home');

        $response->assertOk()
            ->assertJson([
                'state' => 'allArticles',
                'articles' => [],
            ]);
    }








    public function test_it_returns_subscriptions_for_authenticated_user()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id, 'nickname' => 'johndoe']);

        $author = Profile::factory()->create(['nickname' => 'janedoe']);
        Subscription::create([
            'causer' => $profile->nickname,
            'author' => $author->nickname,
        ]);

        $token = $user->createToken('test')->plainTextToken;


        $response = $this->withToken($token)->getJson('/api/home/subscriptions');

        $response->assertOk()
            ->assertJsonStructure([
                'role',
                'state',
                'token',
                'profile' => [
                    'nickname',
                    'account_id',
                    'description',
                    'logo',
                    'followers',
                    'created_at',
                    'updated_at',
                ],
                'subscriptions' => [
                    [
                        'id',
                        'author' => [
                            'nickname',
                            'account_id',
                            'description',
                            'logo',
                            'followers',
                            'created_at',
                            'updated_at',
                        ],
                        'created_at',
                    ],
                ],
            ]);
    }

    public function test_it_returns_404_if_profile_not_found()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/home/subscriptions');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Profile not found.']);
    }

    public function test_it_returns_liked_articles()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id, 'nickname' => 'john']);

        $author = Profile::factory()->create(['nickname' => 'jane']);
        $article = Article::factory()->create(['author' => $author->nickname]);

        \App\Models\LikeReaction::create([
            'causer' => $user->id,
            'article_id' => $article->id,
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/home/liked');

        $response->assertOk()
            ->assertJsonStructure([
                'articles' => [
                    [
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
                        'comments',
                        'thumbnail',
                        'banned_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'role',
                'state',
                'token',
                'profile' => [
                    'nickname',
                    'account_id',
                    'description',
                    'logo',
                    'followers',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_it_returns_empty_articles_if_no_likes()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/home/liked');

        $response->assertOk()
            ->assertJson([
                'articles' => [],
                'role' => $user->role,
                'state' => 'hasProfile',
            ]);
    }

    public function test_it_requires_authentication()
    {
        $response = $this->getJson('/api/home/liked');

        $response->assertUnauthorized();
    }

    public function test_it_returns_liked_article_with_comments_and_likes()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id, 'nickname' => 'john']);

        $authorUser = User::factory()->create();
        $authorProfile = Profile::factory()->create(['user_id' => $authorUser->id, 'nickname' => 'jane']);

        $article = Article::factory()->create(['author' => $authorProfile->nickname]);

        LikeReaction::create([
            'causer' => $user->id,
            'article_id' => $article->id,
        ]);

        $comment1 = Comment::factory()->create([
            'article_id' => $article->id,
            'causer' => $authorUser->id,
        ]);

        $comment2 = Comment::factory()->create([
            'article_id' => $article->id,
            'causer' => $user->id,
        ]);


        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/home/liked');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $article->id,
                'title' => $article->title,
            ])
            ->assertJsonStructure([
                'articles' => [
                    [
                        'id',
                        'author' => ['nickname', 'account_id', 'logo', 'followers'],
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
                                'banned_at',
                                'created_at',
                                'updated_at',
                                'logo',
                            ],
                        ],
                        'thumbnail',
                        'banned_at',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'role',
                'state',
                'token',
                'profile' => ['nickname', 'account_id', 'description', 'logo', 'followers', 'created_at', 'updated_at'],
            ]);
    }


    public function test_search_finds_articles_with_query_in_title()
    {
        $authorUser = User::factory()->create();
        $authorProfile = Profile::factory()->create(['user_id' => $authorUser->id, 'nickname' => 'author']);

        Article::factory()->create(['title' => 'The Quick Brown Fox', 'author' => $authorProfile->nickname]);
        Article::factory()->create(['title' => 'Lazy Dog', 'author' => $authorProfile->nickname]);
        Article::factory()->create(['title' => 'Quick Tips for Coding', 'author' => $authorProfile->nickname]);

        $response = $this->postJson('/api/home/search', ['query' => 'quick']);

        $response->assertOk();

        $titles = array_column($response->json('articles'), 'title');

        $this->assertContains('The Quick Brown Fox', $titles);
        $this->assertContains('Quick Tips for Coding', $titles);
        $this->assertNotContains('Lazy Dog', $titles);
    }

    public function test_search_finds_articles_when_query_is_substring()
    {
        $authorUser = User::factory()->create();
        $authorProfile = Profile::factory()->create(['user_id' => $authorUser->id, 'nickname' => 'author']);

        Article::factory()->create(['title' => 'Introduction to Laravel', 'author' => $authorProfile->nickname]);
        Article::factory()->create(['title' => 'Mastering PHP', 'author' => $authorProfile->nickname]);

        $response = $this->postJson('/api/home/search', ['query' => 'avel']);

        $response->assertOk();

        $titles = array_column($response->json('articles'), 'title');

        $this->assertContains('Introduction to Laravel', $titles);
        $this->assertNotContains('Mastering PHP', $titles);
    }

    public function test_search_returns_empty_when_no_articles_match()
    {
        $authorUser = User::factory()->create();
        $authorProfile = Profile::factory()->create(['user_id' => $authorUser->id, 'nickname' => 'author']);

        Article::factory()->create(['title' => 'Vue.js Basics', 'author' => $authorProfile->nickname]);

        $response = $this->postJson('/api/home/search', ['query' => 'React']);

        $response->assertOk();

        $this->assertEmpty($response->json('articles'));
    }

    public function test_search_requires_query_parameter()
    {
        $response = $this->postJson('/api/home/search', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors('query');
    }
}
