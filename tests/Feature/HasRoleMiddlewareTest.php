<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HasRoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private static int $emailIncrement = 1;

    private function createUserWithRole($role): User
    {
        return User::create([
            'email' => "{$role}" . self::$emailIncrement++ . "@example.com",
            'password' => Hash::make('password'),
            'role' => $role,
            'email_verified_at' => now(),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'checkRole:admin,superadmin'])->post('/test', function () {
            return response()->json(['message' => 'Access granted.']);
        });
    }

    public function test_user_with_user_role_cannot_access_ban_route()
    {
        $user = $this->createUserWithRole('user');
        $token = $user->createToken('token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/test');
        $response->assertStatus(403);
    }

    public function test_user_with_admin_role_can_access_ban_route()
    {
        $admin = $this->createUserWithRole('admin');
        $token = $admin->createToken('token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/test');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Access granted.']);
    }

    public function test_user_with_superadmin_role_can_access_ban_route()
    {
        $superadmin = $this->createUserWithRole('superadmin');
        $token = $superadmin->createToken('token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/test');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Access granted.']);
    }

    public function test_unauthenticated_user_cannot_access_ban_route()
    {
        $response = $this->postJson('/test');
        $response->assertStatus(401);
    }
}
