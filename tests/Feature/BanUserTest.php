<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BanUserTest extends TestCase
{
    use RefreshDatabase;

    protected array $roleLevels;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleLevels = [
            'user' => 0,
            'moder' => 1,
            'admin' => 2,
            'superadmin' => 3,
        ];
    }

    private function createUser(string $role, bool $banned = false): User
    {
        $factory = User::factory()->role($role);
        return $banned ? $factory->banned()->create() : $factory->create();
    }

    public function test_superadmin_can_ban_admin()
    {
        $superadmin = $this->createUser('superadmin');
        $admin = $this->createUser('admin');

        $response = $this->actingAs($superadmin)->postJson("/api/account/{$admin->id}/ban", [
            'reason' => 'Violation of rules.',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($admin->fresh()->banned_at);
    }

    public function test_admin_can_ban_user()
    {
        $admin = $this->createUser('admin');
        $user = $this->createUser('user');

        $response = $this->actingAs($admin)->postJson("/api/account/{$user->id}/ban", [
            'reason' => 'Spamming.',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($user->fresh()->banned_at);
    }

    public function test_admin_cannot_ban_another_admin()
    {
        $admin1 = $this->createUser('admin');
        $admin2 = $this->createUser('admin');

        $response = $this->actingAs($admin1)->postJson("/api/account/{$admin2->id}/ban", [
            'reason' => 'Personal beef.',
        ]);

        $response->assertStatus(403);
        $this->assertNull($admin2->fresh()->banned_at);
    }

    public function test_user_cannot_ban_anyone()
    {
        $user1 = $this->createUser('user');
        $user2 = $this->createUser('user');

        $response = $this->actingAs($user1)->postJson("/api/account/{$user2->id}/ban", [
            'reason' => 'Toxic behavior.',
        ]);

        $response->assertStatus(403);
        $this->assertNull($user2->fresh()->banned_at);
    }

    public function test_superadmin_cannot_ban_self()
    {
        $superadmin = $this->createUser('superadmin');

        $response = $this->actingAs($superadmin)->postJson("/api/account/{$superadmin->id}/ban", [
            'reason' => 'Ban myself!',
        ]);

        $response->assertStatus(403);
        $this->assertNull($superadmin->fresh()->banned_at);
    }

    public function test_ban_fails_without_reason()
    {
        $superadmin = $this->createUser('superadmin');
        $user = $this->createUser('user');

        $response = $this->actingAs($superadmin)->postJson("/api/account/{$user->id}/ban");

        $response->assertStatus(422);
        $this->assertNull($user->fresh()->banned_at);
    }

    public function test_already_banned_user_cannot_be_banned_again()
    {
        $admin = $this->createUser('admin');
        $user = $this->createUser('user', banned: true);

        $response = $this->actingAs($admin)->postJson("/api/account/{$user->id}/ban", [
            'reason' => 'Ban again!',
        ]);

        $response->assertStatus(400);
    }

    public function test_superadmin_can_unban_any_user()
    {
        $superadmin = $this->createUser('superadmin');
        $user = $this->createUser('user', banned: true);

        $response = $this->actingAs($superadmin)->deleteJson("/api/account/{$user->id}/ban", [
            'reason' => 'False positive.',
        ]);

        $response->assertStatus(200);
        $this->assertNull($user->fresh()->banned_at);
    }

    public function test_admin_cannot_unban_another_admin()
    {
        $admin1 = $this->createUser('admin');
        $admin2 = $this->createUser('admin', banned: true);

        $response = $this->actingAs($admin1)->deleteJson("/api/account/{$admin2->id}/ban", [
            'reason' => 'Trying to help.',
        ]);

        $response->assertStatus(403);
        $this->assertNotNull($admin2->fresh()->banned_at);
    }

    public function test_user_cannot_unban_anyone()
    {
        $user1 = $this->createUser('user');
        $user2 = $this->createUser('user', banned: true);

        $response = $this->actingAs($user1)->deleteJson("/api/account/{$user2->id}/ban", [
            'reason' => 'We cool now.',
        ]);

        $response->assertStatus(403);
        $this->assertNotNull($user2->fresh()->banned_at);
    }

    public function test_unban_fails_if_user_is_not_banned()
    {
        $superadmin = $this->createUser('superadmin');
        $user = $this->createUser('user');

        $response = $this->actingAs($superadmin)->deleteJson("/api/account/{$user->id}/ban", [
            'reason' => 'Just checking.',
        ]);

        $response->assertStatus(400);
        $this->assertNull($user->fresh()->banned_at);
    }

    public function test_unban_removes_ban_record()
    {
        $superadmin = $this->createUser('superadmin');
        $user = $this->createUser('user', banned: true);
        $reason = 'Apologized and resolved.';

        DB::table('bans')->insert([
            'causer' => $superadmin->id,
            'target_type' => 'account',
            'target_id' => $user->id,
            'content' => 'Initial ban reason.',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($superadmin)->deleteJson("/api/account/{$user->id}/ban", [
            'reason' => $reason,
        ]);

        $response->assertStatus(200);
        $this->assertNull($user->fresh()->banned_at);
        $this->assertDatabaseMissing('bans', [
            'target_type' => 'account',
            'target_id' => $user->id,
        ]);
    }
}
