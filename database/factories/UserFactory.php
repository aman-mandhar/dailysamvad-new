<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'old_wp_id' => null,
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'slug' => fake()->unique()->slug(2),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'mobile_number' => fake()->optional()->numerify('##########'),
            'avatar_path' => fake()->optional()->imageUrl(),
            'bio' => fake()->optional()->paragraph(),
            'designation' => fake()->optional()->jobTitle(),
            'facebook_url' => fake()->optional()->url(),
            'x_url' => fake()->optional()->url(),
            'instagram_url' => fake()->optional()->url(),
            'youtube_url' => fake()->optional()->url(),
            'is_active' => true,
            'is_public' => true,
            'last_login_at' => null,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
