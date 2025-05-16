<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Note;
use App\Models\Article;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class NoteTest extends TestCase
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
        return $user->createToken('token')->plainTextToken;
    }

    public function test_moderator_can_create_note()
    {
        $moderator = $this->createUserWithRole('moderator');
        $profile = Profile::factory()->create(['user_id' => $moderator->id]);
        $token = $this->authenticate($moderator);

        $response = $this->withToken($token)->postJson("/api/article/{$this->article->id}/note", [
            'content' => 'Test note content',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('notes', ['content' => 'Test note content']);
    }

    public function test_user_cannot_create_note()
    {
        $user = $this->createUserWithRole('user');
        Profile::factory()->create(['user_id' => $user->id]);
        $token = $this->authenticate($user);

        $response = $this->withToken($token)->postJson("/api/article/{$this->article->id}/note", [
            'content' => 'Not allowed',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_note()
    {
        $admin = $this->createUserWithRole('admin');
        Profile::factory()->create(['user_id' => $admin->id]);
        $token = $this->authenticate($admin);

        $response = $this->withToken($token)->postJson("/api/article/{$this->article->id}/note", [
            'content' => 'Admin note',
        ]);

        $response->assertStatus(201);
    }

    public function test_guest_cannot_create_note()
    {
        $response = $this->postJson("/api/article/{$this->article->id}/note", [
            'content' => 'Guest attempt',
        ]);

        $response->assertStatus(401);
    }

    public function test_moderator_can_delete_own_note()
    {
        $moderator = $this->createUserWithRole('moderator');
        $profile = Profile::factory()->create(['user_id' => $moderator->id]);
        $token = $this->authenticate($moderator);

        $note = Note::factory()->create([
            'causer' => $profile->nickname,
            'article_id' => $this->article->id,
        ]);

        $response = $this->withToken($token)->deleteJson("/api/note/{$note->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_moderator_cannot_delete_others_note()
    {
        $author = $this->createUserWithRole('moderator');
        $authorProfile = Profile::factory()->create(['user_id' => $author->id]);

        $note = Note::factory()->create([
            'causer' => $authorProfile->nickname,
            'article_id' => $this->article->id,
        ]);

        $otherMod = $this->createUserWithRole('moderator');
        Profile::factory()->create(['user_id' => $otherMod->id]);
        $token = $this->authenticate($otherMod);

        $response = $this->withToken($token)->deleteJson("/api/note/{$note->id}");
        $response->assertStatus(403);
    }

    public function test_admin_can_delete_any_note()
    {
        $noteAuthor = $this->createUserWithRole('moderator');
        $profile = Profile::factory()->create(['user_id' => $noteAuthor->id]);

        $note = Note::factory()->create([
            'causer' => $profile->nickname,
            'article_id' => $this->article->id,
        ]);

        $admin = $this->createUserWithRole('admin');
        Profile::factory()->create(['user_id' => $admin->id]);
        $token = $this->authenticate($admin);

        $response = $this->withToken($token)->deleteJson("/api/note/{$note->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }
}
