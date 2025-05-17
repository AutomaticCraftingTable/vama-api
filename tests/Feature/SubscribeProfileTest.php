<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Profile;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscribeProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_subscribe_to_profile()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);
        $targetProfile = Profile::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/profile/{$targetProfile->nickname}/subscribe");

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Subscribed successfully.']);

        $this->assertDatabaseHas('subscriptions', [
            'causer' => $profile->nickname,
            'author' => $targetProfile->nickname,
        ]);
    }


    public function test_user_cannot_subscribe_to_self()
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/profile/{$profile->nickname}/subscribe");

        $response->assertStatus(400)
                 ->assertJson(['message' => 'You cannot subscribe to yourself.']);
    }

    public function test_user_can_unsubscribe()
    {
        $user = User::factory()->create();
        $userProfile = Profile::factory()->create(['user_id' => $user->id]);

        $author = User::factory()->create();
        $authorProfile = Profile::factory()->create(['user_id' => $author->id]);

        Subscription::create([
            'causer' => $userProfile->nickname,
            'author' => $authorProfile->nickname,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/profile/{$authorProfile->nickname}/subscribe");

        $response->assertOk()
                 ->assertJson(['message' => 'Unsubscribed successfully.']);

        $this->assertDatabaseMissing('subscriptions', [
            'causer' => $userProfile->nickname,
            'author' => $authorProfile->nickname,
        ]);
    }


    public function test_unsubscribe_from_non_existing_subscription()
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);

        $author = User::factory()->create();
        $authorProfile = Profile::factory()->create(['user_id' => $author->id]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/profile/{$authorProfile->nickname}/subscribe");

        $response->assertStatus(404)
                 ->assertJson(['message' => 'Subscription not found.']);
    }
}
