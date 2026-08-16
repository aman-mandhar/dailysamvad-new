<?php

namespace Database\Factories;

use App\Models\PushTopic;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PushTopic> */
class PushTopicFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'type' => 'category',
            'is_active' => true,
            'is_default' => false,
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
