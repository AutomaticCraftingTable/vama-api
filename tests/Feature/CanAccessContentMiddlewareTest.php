<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CanAccessContentMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email_verified_at' => now(),
            'banned_at' => null,
        ], $overrides));
    }

    protected function setUp(): void
    {
        parent::setUp();


        Route::middleware(['auth:sanctum', 'canAccessContent'])->get('/test-access', function () {
            return response()->json(['message' => 'OK']);
        });
    }

    public function test_verified_and_not_banned_user_can_access()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->getJson('/test-access');
        $response->assertStatus(200);
        $response->assertJson(['message' => 'OK']);
    }

    public function test_guest_cannot_access()
    {
        $response = $this->getJson('/test-access');
        $response->assertStatus(401);
    }

    public function test_unverified_user_cannot_access()
    {
        $user = $this->createUser(['email_verified_at' => null]);

        $response = $this->actingAs($user)->getJson('/test-access');
        $response->assertStatus(403);
        $response->assertJson(['error' => 'Email not verified.']);
    }

    public function test_banned_user_cannot_access()
    {
        $user = $this->createUser(['banned_at' => Carbon::now()]);

        $response = $this->actingAs($user)->getJson('/test-access');
        $response->assertStatus(403);
        $response->assertJson(['error' => 'User is banned.']);
    }
}
