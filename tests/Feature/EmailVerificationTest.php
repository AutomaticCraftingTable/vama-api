<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email_verified_at' => null]);
        $token = $user->createToken('test')->plainTextToken;

        $this->postJson('/api/email/verification-notification', [], [
            'Authorization' => "Bearer $token",
        ])
        ->assertOk()
        ->assertJson(['message' => 'Verification email sent']);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_user_can_verify_email_and_activity_is_logged(): void
    {
        $user = User::factory()->unverified()->create();

        $token = $user->createToken('test')->plainTextToken;

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->getJson($verificationUrl, [
            'Authorization' => "Bearer $token",
        ]);

        $response->assertOk()
                 ->assertJson(['message' => 'Email verified successfully']);

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->assertDatabaseHas('activities', [
            'subject_id' => $user->id,
            'subject_type' => get_class($user),
            'causer_id' => $user->id,
            'event' => 'verified',
        ]);
    }
}
