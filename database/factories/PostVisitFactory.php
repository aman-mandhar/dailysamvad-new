<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostVisit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PostVisit> */
class PostVisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'visitor_id' => null,
            'visitor_uuid' => (string) Str::uuid(),
            'session_id' => Str::random(40),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'referrer_url' => fake()->optional()->url(),
            'source' => null,
            'medium' => null,
            'campaign' => null,
            'device_type' => null,
            'browser' => null,
            'platform' => null,
            'country' => null,
            'region' => null,
            'city' => null,
            'visited_at' => now(),
        ];
    }
}
