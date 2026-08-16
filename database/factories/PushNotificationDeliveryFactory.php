<?php

namespace Database\Factories;

use App\Models\PushNotification;
use App\Models\PushNotificationDelivery;
use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PushNotificationDelivery> */
class PushNotificationDeliveryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'push_notification_id' => PushNotification::factory(),
            'push_subscription_id' => PushSubscription::factory(),
            'subscription_token_hash' => hash('sha256', fake()->uuid()),
            'status' => 'queued',
            'queued_at' => now(),
        ];
    }
}
