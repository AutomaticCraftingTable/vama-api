<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\LikeReaction;
use App\Models\Note;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_all_users_and_profiles()
    {
        $this->seed(\Database\Seeders\UserWithProfilesSeeder::class);

        $this->assertDatabaseCount('users', 29);
        $this->assertDatabaseCount('profiles', 29);

        $this->assertEquals(1, User::where('role', 'superadmin')->count());
        $this->assertEquals(3, User::where('role', 'admin')->count());
        $this->assertEquals(5, User::where('role', 'moderator')->count());
        $this->assertEquals(20, User::where('role', 'user')->count());

        $this->assertEquals(29, User::whereNotNull('email_verified_at')->count());

        $superadmin = User::where('role', 'superadmin')->first();
        $this->assertNotNull($superadmin);
        $this->assertEquals('test@example.com', $superadmin->email);
        $this->assertNotNull($superadmin->profile);
    }

    public function test_ban_seeder_bans_three_existing_users()
    {
        $this->seed(\Database\Seeders\UserWithProfilesSeeder::class);

        $this->seed(\Database\Seeders\BanSeeder::class);

        $this->assertDatabaseCount('bans', 3);

        $bannedUserIds = DB::table('bans')
            ->where('target_type', 'account')
            ->pluck('target_id')
            ->toArray();

        foreach ($bannedUserIds as $userId) {
            $this->assertDatabaseHas('users', ['id' => $userId]);
        }

        $admin = DB::table('bans')->value('causer');
        $this->assertNotNull($admin);
        $this->assertDatabaseHas('users', [
            'id' => $admin,
            'role' => 'admin',
        ]);
    }

    public function test_articles_and_article_bans_are_seeded_properly()
    {
        $this->seed(\Database\Seeders\UserWithProfilesSeeder::class);
        $this->seed(\Database\Seeders\ArticleSeeder::class);
        $this->seed(\Database\Seeders\BanSeeder::class);

        $this->assertDatabaseCount('articles', 25);

        $articleBans = DB::table('bans')->where('target_type', 'article')->get();
        $this->assertCount(3, $articleBans);

        foreach ($articleBans as $ban) {
            $this->assertDatabaseHas('articles', ['id' => $ban->target_id]);
        }

        $adminId = User::where('role', 'admin')->first()?->id;
        $this->assertTrue($articleBans->pluck('causer')->unique()->contains($adminId));
    }

    public function test_comments_and_bans_are_seeded_properly()
    {
        $this->seed(\Database\Seeders\UserWithProfilesSeeder::class);
        $this->seed(\Database\Seeders\ArticleSeeder::class);
        $this->seed(\Database\Seeders\CommentSeeder::class);
        $this->seed(\Database\Seeders\BanSeeder::class);

        $this->assertDatabaseCount('comments', 50);

        $commentBans = DB::table('bans')->where('target_type', 'comment')->get();
        $this->assertCount(5, $commentBans);

        foreach ($commentBans as $ban) {
            $this->assertDatabaseHas('comments', ['id' => $ban->target_id]);
        }

        $adminId = User::where('role', 'admin')->first()?->id;
        $this->assertTrue($commentBans->pluck('causer')->unique()->contains($adminId));
    }

    public function test_like_reactions_seeder_creates_35_likes()
    {
        $this->seed(\Database\Seeders\UserWithProfilesSeeder::class);
        $this->seed(\Database\Seeders\ArticleSeeder::class);
        $this->seed(\Database\Seeders\LikeReactionSeeder::class);

        $this->assertDatabaseCount('like_reactions', 35);

        $this->assertNotNull(LikeReaction::first());
        $this->assertNotNull(LikeReaction::inRandomOrder()->first()->causer);
        $this->assertNotNull(LikeReaction::inRandomOrder()->first()->article_id);
    }

    public function test_notes_are_seeded_with_existing_profiles_and_articles()
    {
        $this->seed(\Database\Seeders\UserWithProfilesSeeder::class);
        $this->seed(\Database\Seeders\ArticleSeeder::class);
        $this->seed(\Database\Seeders\NoteSeeder::class);

        $this->assertDatabaseCount('notes', 20);

        $note = Note::inRandomOrder()->first();
        $this->assertNotNull($note);
        $this->assertNotEmpty($note->causer);
        $this->assertNotNull($note->article_id);
    }

     public function test_subscriptions_are_created_with_existing_profiles()
    {
        $this->seed(\Database\Seeders\UserWithProfilesSeeder::class);
        $this->seed(\Database\Seeders\SubscriptionSeeder::class);

        $this->assertGreaterThanOrEqual(1, Subscription::count());

        $subscription = Subscription::first();
        $this->assertNotEquals($subscription->causer, $subscription->author);
    }

     public function test_reports_are_created_for_each_type()
    {
        $this->seed(\Database\Seeders\UserWithProfilesSeeder::class);
        $this->seed(\Database\Seeders\ArticleSeeder::class);
        $this->seed(\Database\Seeders\CommentSeeder::class);
        $this->seed(\Database\Seeders\ReportSeeder::class);

        $this->assertEquals(5, DB::table('reports')->where('target_type', 'profile')->count());
        $this->assertEquals(5, DB::table('reports')->where('target_type', 'article')->count());
        $this->assertEquals(5, DB::table('reports')->where('target_type', 'comment')->count());

        $this->assertEquals(15, DB::table('reports')->count());
    }
}
