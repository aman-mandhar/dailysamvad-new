<?php

namespace Database\Factories;

use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PushSubscription> */
class PushSubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $token = 'fake-fcm-'.Str::random(180);

        return [
            'user_id' => null,
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'device_uuid' => fake()->uuid(),
            'browser' => 'Chrome',
            'browser_version' => '130',
            'platform' => 'Windows',
            'device_type' => 'desktop',
            'language' => 'en-IN',
            'timezone' => 'Asia/Kolkata',
            'user_agent' => fake()->userAgent(),
            'permission_status' => 'granted',
            'is_active' => true,
            'last_seen_at' => now(),
            'last_registered_at' => now(),
            'unsubscribed_at' => null,
        ];
    }
}
