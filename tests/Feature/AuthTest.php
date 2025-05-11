<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Mockery;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user' => ['id', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_can_login()
    {
        User::create([
            'email' => 'login@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user' => ['id', 'email'], 'token']);
    }

    public function test_login_fails_with_invalid_credentials()
    {
        User::create([
            'email' => 'wrong@example.com',
            'password' => Hash::make('correctpassword'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'The provided credentials are incorrect.',
                 ]);
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'You are logged out.']);
    }

    public function test_user_cannot_register_with_duplicate_email()
    {
        User::create([
            'email' => 'duplicate@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'duplicate@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('email');
    }


    public function test_user_can_get_selfinfo()
    {
        $user = User::create([
            'email' => 'authuser@example.com',
            'password' => Hash::make('password'),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200)
                 ->assertJsonStructure(['id', 'email'])
                 ->assertJsonFragment(['email' => 'authuser@example.com']);
    }

    public function test_unauthenticated_user_cant_get_selfinfo()
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }
    public function test_user_registration_sets_default_role_and_banned_at_null()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'roleuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'email' => 'roleuser@example.com',
            'role' => 'user',
            'banned_at' => null,
        ]);
    }



    public function test_handle_google_callback_creates_user_and_returns_token()
    {
        $mockUser = Mockery::mock(ProviderUser::class);
        $mockUser->shouldReceive('getEmail')->andReturn('newgoogleuser@example.com');
        $mockUser->shouldReceive('getId')->andReturn('google-12345');

        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($mockUser);

        $response = $this->get('/auth/callback/google');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'email'],
            'token',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newgoogleuser@example.com',
            'google_id' => 'google-12345',
        ]);
    }

    public function test_google_login_sets_default_role_and_banned_at_null()
    {
        $mockUser = Mockery::mock(ProviderUser::class);
        $mockUser->shouldReceive('getEmail')->andReturn('googlerole@example.com');
        $mockUser->shouldReceive('getId')->andReturn('google-role-123');

        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($mockUser);

        $response = $this->get('/auth/callback/google');

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'email' => 'googlerole@example.com',
            'google_id' => 'google-role-123',
            'role' => 'user',
            'banned_at' => null,
        ]);
    }


    public function test_google_login_fails_if_email_already_registered_without_google_id()
    {
        User::create([
            'email' => 'existing@example.com',
            'password' => bcrypt('password'),
            'google_id' => null,
        ]);

        $abstractUser = Mockery::mock(ProviderUser::class);
        $abstractUser->shouldReceive('getEmail')->andReturn('existing@example.com');
        $abstractUser->shouldReceive('getName')->andReturn('Google User');
        $abstractUser->shouldReceive('getId')->andReturn('google-9999');

        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($abstractUser);

        $response = $this->get('/auth/callback/google');

        $response->assertStatus(409);
        $response->assertJson([
            'message' => 'This email is already registered. Please log in with email and password.',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'existing@example.com',
            'google_id' => 'google-9999',
        ]);
    }
}
