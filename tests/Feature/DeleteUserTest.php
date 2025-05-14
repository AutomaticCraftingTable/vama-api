<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteUserTest extends TestCase
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

    private function createUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_admin_can_delete_account()
    {
        $admin = $this->createUser('admin');
        $user = $this->createUser('user');

        $response = $this->actingAs($admin)->deleteJson("/api/account/{$user->id}");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Account deleted successfully.']);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_superadmin_account()
    {
        $admin = $this->createUser('admin');
        $superadmin = $this->createUser('superadmin');

        $response = $this->actingAs($admin)->deleteJson("/api/account/{$superadmin->id}");

        $response->assertStatus(403);
        $response->assertJson(['error' => 'You are not allowed to delete a superadmin account.']);
    }


    public function test_superadmin_can_delete_admin_account()
    {
        $superadmin = $this->createUser('superadmin');
        $admin = $this->createUser('admin');

        $response = $this->actingAs($superadmin)->deleteJson("/api/account/{$admin->id}");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Account deleted successfully.']);
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }

    public function test_user_cannot_delete_any_account()
    {
        $user1 = $this->createUser('user');
        $user2 = $this->createUser('user');

        $response = $this->actingAs($user1)->deleteJson("/api/account/{$user2->id}");

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Unauthorized']);
    }

    public function test_delete_fails_if_account_does_not_exist()
    {
        $superadmin = $this->createUser('superadmin');

        $response = $this->actingAs($superadmin)->deleteJson("/api/account/9999");

        $response->assertStatus(404);
        $response->assertJson(['error' => 'User not found.']);
    }
}
