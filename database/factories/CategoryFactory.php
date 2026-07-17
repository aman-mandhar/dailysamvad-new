<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'old_wp_id' => null,
            'parent_id' => null,
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->optional()->paragraph(),
            'image_path' => fake()->optional()->imageUrl(),
            'meta_title' => fake()->optional()->sentence(6),
            'meta_description' => fake()->optional()->sentence(12),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
            'show_in_menu' => true,
        ];
    }

    /**
     * Indicate that the category has no parent.
     */
    public function parent(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => null,
        ]);
    }

    /**
     * Indicate that the category is a child category.
     */
    public function child(?Category $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->getKey() ?? Category::factory(),
        ]);
    }
}
