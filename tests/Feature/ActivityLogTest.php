<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticateAs(string $role): string
    {
        $user = User::factory()->create(['role' => $role]);
        $token = $user->createToken('TestToken')->plainTextToken;
        return 'Bearer ' . $token;
    }

    public function test_admin_can_see_own_activity()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('TestToken')->plainTextToken;

        activity('users')
            ->causedBy($admin)
            ->performedOn($admin)
            ->log('Admin action');

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/api/activities');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'description' => 'Admin action',
                'causer_id' => $admin->id,
                'log_name' => 'users',
                'status' => 'success',
            ]);
    }

    public function test_superadmin_can_see_admin_activities()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $superadmin = User::factory()->create(['role' => 'superadmin']);
        $token = $superadmin->createToken('TestToken')->plainTextToken;

        activity('users')
            ->causedBy($admin)
            ->performedOn($admin)
            ->log('Admin created user');

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/api/activities/admins');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'description' => 'Admin created user',
                'causer_id' => $admin->id,
                'log_name' => 'users',
                'status' => 'success',
            ]);
    }

    public function test_guest_cannot_access_activity()
    {
        $response = $this->getJson('/api/activities');
        $response->assertStatus(401);
    }

    public function test_non_superadmin_cannot_see_admin_activities()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('TestToken')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/api/activities/admins');

        $response->assertStatus(403);
    }
}
