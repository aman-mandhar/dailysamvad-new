<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $createdAt = fake()->dateTimeBetween('-6 months', 'now');
        $hasFeaturedImage = fake()->boolean(70);
        $headline = rtrim(fake()->sentence(fake()->numberBetween(6, 12)), '.');

        return [
            'old_wp_id' => null,
            'author_id' => User::factory(),
            'title' => $headline,
            'slug' => fake()->unique()->slug(fake()->numberBetween(5, 9)),
            'excerpt' => fake()->sentences(2, true),
            'content' => '<p>'.implode('</p><p>', fake()->paragraphs(fake()->numberBetween(5, 10))).'</p>',
            'featured_image' => $hasFeaturedImage ? fake()->imageUrl(1200, 675) : null,
            'featured_image_alt' => $hasFeaturedImage ? $headline : null,
            'featured_image_caption' => $hasFeaturedImage ? fake()->sentence(10) : null,
            'status' => PostStatus::Draft,
            'language' => fake()->randomElement(['hi', 'pa', 'en']),
            'is_breaking' => false,
            'is_featured' => false,
            'allow_comments' => true,
            'views_count' => 0,
            'likes_count' => 0,
            'published_at' => null,
            'scheduled_at' => null,
            'meta_title' => null,
            'meta_description' => null,
            'focus_keyword' => null,
            'canonical_url' => null,
            'old_url' => null,
            'source_url' => null,
            'source_name' => null,
            'source_data' => null,
            'seo_data' => null,
            'created_at' => $createdAt,
            'updated_at' => fake()->dateTimeBetween($createdAt, 'now'),
        ];
    }

    /**
     * Indicate that the post has complete SEO metadata.
     */
    public function withSeo(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_title' => Str::limit($attributes['title'], 60, ''),
            'meta_description' => Str::limit($attributes['excerpt'], 160, ''),
            'focus_keyword' => Str::lower(implode(' ', array_slice(explode(' ', $attributes['title']), 0, 3))),
            'canonical_url' => 'https://www.dailysamvad.test/news/'.$attributes['slug'],
            'seo_data' => [
                'robots' => [
                    'index' => true,
                    'follow' => true,
                ],
                'open_graph' => [
                    'type' => 'article',
                    'title' => $attributes['title'],
                ],
            ],
        ]);
    }

    /**
     * Indicate that the post was imported from WordPress.
     */
    public function importedFromWordPress(): static
    {
        return $this->state(function (array $attributes) {
            $oldWpId = fake()->unique()->numberBetween(1, 10_000_000);
            $oldUrl = 'http://localhost/dailysamvad-old/?p='.$oldWpId;
            $publishedAt = fake()->dateTimeBetween('-10 years', '-1 day');

            return [
                'old_wp_id' => $oldWpId,
                'old_url' => $oldUrl,
                'source_name' => 'WordPress',
                'status' => PostStatus::Published,
                'published_at' => $publishedAt,
                'created_at' => $publishedAt,
                'updated_at' => fake()->dateTimeBetween($publishedAt, 'now'),
                'source_data' => [
                    'platform' => 'wordpress',
                    'post_id' => $oldWpId,
                    'original_url' => $oldUrl,
                    'post_type' => 'post',
                ],
            ];
        });
    }

    /**
     * Indicate that the post is scheduled for future publication.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Scheduled,
            'published_at' => null,
            'scheduled_at' => fake()->dateTimeBetween('+1 hour', '+1 month'),
        ]);
    }

    /**
     * Indicate that the post is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Published,
            'published_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'scheduled_at' => null,
        ]);
    }

    /**
     * Indicate that the post is breaking news.
     */
    public function breaking(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_breaking' => true,
        ]);
    }

    /**
     * Indicate that the post is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }
}
