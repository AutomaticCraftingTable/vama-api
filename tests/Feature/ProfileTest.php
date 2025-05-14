<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function createUser(): User
    {
        return User::factory()->create();
    }

    public function test_user_can_create_profile()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->postJson('/api/profile', [
            'nickname' => 'john_doe',
            'description' => 'Just a test user.',
            'logo' => 'logo.png',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['profile' => ['nickname', 'description', 'logo']]);

        $this->assertDatabaseHas('profiles', [
            'nickname' => 'john_doe',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_create_multiple_profiles()
    {
        $user = $this->createUser();
        Profile::factory()->for($user)->create([
            'nickname' => 'existing',
        ]);

        $response = $this->actingAs($user)->postJson('/api/profile', [
            'nickname' => 'new_nick',
        ]);

        $response->assertStatus(409)
                 ->assertJson(['message' => 'Profile already exists.']);
    }

    public function test_user_can_update_profile()
    {
        $user = $this->createUser();
        Profile::factory()->for($user)->create([
            'nickname' => 'original',
            'description' => 'Old description',
        ]);

        $response = $this->actingAs($user)->putJson('/api/profile', [
            'description' => 'Updated!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['description' => 'Updated!']);

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'description' => 'Updated!',
        ]);
    }

    public function test_user_cannot_update_profile_if_none_exists()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->putJson('/api/profile', [
            'description' => 'Trying to update',
        ]);

        $response->assertStatus(404);
    }

    public function test_user_can_delete_profile()
    {
        $user = $this->createUser();
        Profile::factory()->for($user)->create([
            'nickname' => 'to_be_deleted',
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/profile');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Profile deleted.']);

        $this->assertDatabaseMissing('profiles', [
            'user_id' => $user->id,
        ]);
    }

    public function test_user_cannot_delete_profile_if_none_exists()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->deleteJson('/api/profile');

        $response->assertStatus(404);
    }
}
