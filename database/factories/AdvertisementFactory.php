<?php

namespace Database\Factories;

use App\Enums\AdvertisementStatus;
use App\Models\Advertisement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Advertisement> */
class AdvertisementFactory extends Factory
{
    protected $model = Advertisement::class;

    public function definition(): array
    {
        $title = fake()->unique()->company().' campaign';

        return ['uuid' => (string) Str::uuid(), 'title' => $title, 'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999), 'status' => AdvertisementStatus::Active, 'priority' => 0, 'rotation_weight' => 1, 'open_in_new_tab' => true, 'nofollow' => true, 'sponsored' => true];
    }
}
