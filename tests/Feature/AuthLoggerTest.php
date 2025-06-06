<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

class AuthLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_logs_activity()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'User registered',
            'log_name' => 'auth',
        ]);
    }

    public function test_login_logs_activity()
    {
        $user = User::create([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'User logged in',
            'log_name' => 'auth',
        ]);
    }

    public function test_logout_logs_activity()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'User logged out',
            'log_name' => 'auth',
        ]);
    }

public function test_google_login_logs_activity()
{
    $user = \App\Models\User::factory()->create([
        'email' => 'googleuser@example.com',
        'google_id' => '1234567890',
    ]);

    $mockSocialiteUser = \Mockery::mock(\Laravel\Socialite\Contracts\User::class);
    $mockSocialiteUser->shouldReceive('getEmail')->andReturn('googleuser@example.com');
    $mockSocialiteUser->shouldReceive('getId')->andReturn('1234567890');

    $mockProvider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $mockProvider->shouldReceive('stateless')->andReturnSelf();
    $mockProvider->shouldReceive('user')->andReturn($mockSocialiteUser);

    \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($mockProvider);

    $response = $this->getJson('/api/auth/callback/google');

    $response->assertStatus(200);

    $this->assertDatabaseHas('activity_log', [
        'description' => 'User logged in via Google',
        'log_name' => 'auth',
    ]);
}

}
