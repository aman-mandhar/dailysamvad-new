<?php

namespace Tests\Feature\Push;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_a_push_subscription(): void
    {
        $token = $this->token('guest');

        $this->postJson(route('push.subscriptions.store'), $this->payload($token))
            ->assertCreated()
            ->assertExactJson(['success' => true, 'status' => 'subscribed'])
            ->assertJsonMissing(['token' => $token]);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => null,
            'token_hash' => hash('sha256', $token),
            'is_active' => true,
            'permission_status' => 'granted',
        ]);
    }

    public function test_authenticated_registration_uses_request_user_and_ignores_injected_user_id(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $token = $this->token('authenticated');

        $this->actingAs($user)->postJson(route('push.subscriptions.store'), [
            ...$this->payload($token),
            'user_id' => $other->getKey(),
            'token_hash' => str_repeat('0', 64),
            'ip_address' => '203.0.113.99',
        ])->assertCreated();

        $this->assertDatabaseHas('push_subscriptions', [
            'token_hash' => hash('sha256', $token),
            'user_id' => $user->getKey(),
        ]);
        $this->assertDatabaseMissing('push_subscriptions', ['token_hash' => str_repeat('0', 64)]);
    }

    public function test_guest_cannot_inject_a_user_association(): void
    {
        $user = User::factory()->create();
        $token = $this->token('injection');

        $this->postJson(route('push.subscriptions.store'), [
            ...$this->payload($token),
            'user_id' => $user->getKey(),
        ])->assertCreated();

        $this->assertDatabaseHas('push_subscriptions', [
            'token_hash' => hash('sha256', $token),
            'user_id' => null,
        ]);
    }

    public function test_registration_is_idempotent_and_reactivates_an_existing_token(): void
    {
        $token = $this->token('repeat');
        PushSubscription::factory()->create([
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'is_active' => false,
            'permission_status' => 'default',
            'unsubscribed_at' => now()->subDay(),
        ]);

        $this->postJson(route('push.subscriptions.store'), $this->payload($token))
            ->assertOk()
            ->assertJsonPath('status', 'updated');
        $this->postJson(route('push.subscriptions.store'), $this->payload($token))->assertOk();

        $this->assertSame(1, PushSubscription::query()->where('token_hash', hash('sha256', $token))->count());
        $subscription = PushSubscription::query()->firstOrFail();
        $this->assertTrue($subscription->is_active);
        $this->assertNull($subscription->unsubscribed_at);
    }

    public function test_authenticated_user_claims_anonymous_token_and_account_switching_is_deterministic(): void
    {
        $token = $this->token('claim');
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        PushSubscription::factory()->create([
            'user_id' => null,
            'token' => $token,
            'token_hash' => hash('sha256', $token),
        ]);

        $this->actingAs($firstUser)->postJson(route('push.subscriptions.store'), $this->payload($token))->assertOk();
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $firstUser->getKey()]);

        $this->actingAs($secondUser)->postJson(route('push.subscriptions.store'), $this->payload($token))->assertOk();
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $secondUser->getKey()]);
        $this->assertSame(1, PushSubscription::query()->count());
    }

    public function test_same_user_can_register_multiple_devices(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('push.subscriptions.store'), $this->payload($this->token('desktop')))->assertCreated();
        $this->actingAs($user)->postJson(route('push.subscriptions.store'), [
            ...$this->payload($this->token('mobile')),
            'device_uuid' => '123e4567-e89b-42d3-a456-426614174001',
            'device_type' => 'mobile',
        ])->assertCreated();

        $this->assertSame(2, $user->pushSubscriptions()->active()->count());
    }

    public function test_new_token_for_same_device_deactivates_rotated_token(): void
    {
        $oldToken = $this->token('old');
        $newToken = $this->token('new');
        $deviceUuid = '123e4567-e89b-42d3-a456-426614174099';
        PushSubscription::factory()->create([
            'token' => $oldToken,
            'token_hash' => hash('sha256', $oldToken),
            'device_uuid' => $deviceUuid,
        ]);

        $this->postJson(route('push.subscriptions.store'), [
            ...$this->payload($newToken),
            'device_uuid' => $deviceUuid,
        ])->assertCreated();

        $this->assertDatabaseHas('push_subscriptions', ['token_hash' => hash('sha256', $oldToken), 'is_active' => false]);
        $this->assertDatabaseHas('push_subscriptions', ['token_hash' => hash('sha256', $newToken), 'is_active' => true]);
    }

    public function test_unsubscribe_marks_the_subscription_inactive_without_deleting_it(): void
    {
        $token = $this->token('unsubscribe');
        PushSubscription::factory()->create(['token' => $token, 'token_hash' => hash('sha256', $token)]);

        $this->deleteJson(route('push.subscriptions.destroy'), ['token' => $token])
            ->assertOk()
            ->assertExactJson(['success' => true, 'status' => 'unsubscribed']);

        $subscription = PushSubscription::query()->firstOrFail();
        $this->assertFalse($subscription->is_active);
        $this->assertNotNull($subscription->unsubscribed_at);
    }

    public function test_invalid_registration_and_unsubscribe_payloads_are_rejected(): void
    {
        $this->assertSame(422, $this->postJson(route('push.subscriptions.store'), [])->getStatusCode());
        $this->assertSame(422, $this->postJson(route('push.subscriptions.store'), $this->payload('short'))->getStatusCode());
        $this->assertSame(422, $this->deleteJson(route('push.subscriptions.destroy'), [])->getStatusCode());
        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    private function payload(string $token): array
    {
        return [
            'token' => $token,
            'device_uuid' => '123e4567-e89b-42d3-a456-426614174000',
            'browser' => 'Chrome',
            'browser_version' => '130.0',
            'platform' => 'Windows',
            'device_type' => 'desktop',
            'language' => 'en-IN',
            'timezone' => 'Asia/Kolkata',
            'permission_status' => 'granted',
        ];
    }

    private function token(string $label): string
    {
        return $label.'.'.str_repeat('x', 160);
    }
}
