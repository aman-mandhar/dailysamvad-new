<?php

namespace Database\Factories;

use App\Enums\PushNotificationStatus;
use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PushNotification> */
class PushNotificationFactory extends Factory
{
    protected $model = PushNotification::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),
            'title' => fake()->sentence(6),
            'body' => fake()->sentence(15),
            'status' => PushNotificationStatus::Draft,
        ];
    }
}
