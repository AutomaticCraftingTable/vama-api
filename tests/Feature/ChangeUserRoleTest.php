<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangeUserRoleTest extends TestCase
{
    use RefreshDatabase;

    protected array $roleLevels;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleLevels = [
            'user' => 0,
            'moderator' => 1,
            'admin' => 2,
            'superadmin' => 3,
        ];
    }

    private function createUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    public function test_superadmin_can_change_any_role()
    {
        $superadmin = $this->createUser('superadmin');
        $target = $this->createUser('admin');

        $response = $this->actingAs($superadmin)->postJson("/api/account/{$target->id}/role", [
            'role' => 'moderator',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('moderator', $target->fresh()->role);
    }

    public function test_admin_can_change_user_to_moderator()
    {
        $admin = $this->createUser('admin');
        $target = $this->createUser('user');

        $response = $this->actingAs($admin)->postJson("/api/account/{$target->id}/role", [
            'role' => 'moderator',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('moderator', $target->fresh()->role);
    }

    public function test_admin_cannot_assign_admin_or_superadmin_role()
    {
        $admin = $this->createUser('admin');
        $target = $this->createUser('user');

        foreach (['admin', 'superadmin'] as $forbiddenRole) {
            $response = $this->actingAs($admin)->postJson("/api/account/{$target->id}/role", [
                'role' => $forbiddenRole,
            ]);
            $response->assertStatus(403);
        }
    }

    public function test_admin_cannot_change_role_of_another_admin_or_higher()
    {
        $admin = $this->createUser('admin');
        $higherRoles = ['admin', 'superadmin'];

        foreach ($higherRoles as $role) {
            $target = $this->createUser($role);

            $response = $this->actingAs($admin)->postJson("/api/account/{$target->id}/role", [
                'role' => 'user',
            ]);

            $response->assertStatus(403);
            $this->assertEquals($role, $target->fresh()->role);
        }
    }

    public function test_user_cannot_change_roles()
    {
        $user = $this->createUser('user');
        $target = $this->createUser('user');

        $response = $this->actingAs($user)->postJson("/api/account/{$target->id}/role", [
            'role' => 'moderator',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('user', $target->fresh()->role);
    }

    public function test_invalid_role_fails_validation()
    {
        $superadmin = $this->createUser('superadmin');
        $target = $this->createUser('user');

        $response = $this->actingAs($superadmin)->postJson("/api/account/{$target->id}/role", [
            'role' => 'invalidrole',
        ]);

        $response->assertStatus(422);
        $this->assertEquals('user', $target->fresh()->role);
    }
}
