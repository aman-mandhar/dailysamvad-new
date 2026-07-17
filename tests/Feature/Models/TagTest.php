<?php

namespace Tests\Feature\Models;

use App\Models\Tag;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_tag_can_be_created(): void
    {
        $tag = Tag::factory()->create([
            'old_wp_id' => 42,
            'name' => 'Breaking News',
            'slug' => 'breaking-news',
            'description' => 'News requiring immediate attention.',
        ]);

        $this->assertDatabaseHas('tags', [
            'id' => $tag->id,
            'old_wp_id' => 42,
            'name' => 'Breaking News',
            'slug' => 'breaking-news',
            'description' => 'News requiring immediate attention.',
        ]);
    }

    public function test_slug_must_be_unique(): void
    {
        Tag::factory()->create(['slug' => 'politics']);

        $this->expectException(QueryException::class);

        Tag::factory()->create(['slug' => 'politics']);
    }

    public function test_old_wp_id_must_be_unique_when_present(): void
    {
        Tag::factory()->create(['old_wp_id' => 123]);

        $this->expectException(QueryException::class);

        Tag::factory()->create(['old_wp_id' => 123]);
    }

    public function test_nullable_old_wp_id_allows_multiple_null_values(): void
    {
        Tag::factory()->count(2)->create(['old_wp_id' => null]);

        $this->assertDatabaseCount('tags', 2);
    }

    public function test_ordered_scope_returns_tags_alphabetically_by_name(): void
    {
        Tag::factory()->create(['name' => 'World']);
        Tag::factory()->create(['name' => 'Business']);
        Tag::factory()->create(['name' => 'Culture']);

        $this->assertSame(
            ['Business', 'Culture', 'World'],
            Tag::query()->ordered()->pluck('name')->all(),
        );
    }
}
