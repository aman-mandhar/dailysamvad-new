<?php

namespace Tests\Feature\Models;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PostHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_composite_indexes_exist(): void
    {
        $indexNames = collect(Schema::getIndexes('posts'))->pluck('name');

        $this->assertContains('posts_status_published_at_index', $indexNames);
        $this->assertContains('posts_status_scheduled_at_index', $indexNames);
    }

    public function test_duplicate_old_wp_id_is_prevented(): void
    {
        Post::factory()->create(['old_wp_id' => 9876]);

        $this->expectException(QueryException::class);

        Post::factory()->create(['old_wp_id' => 9876]);
    }

    public function test_duplicate_historical_urls_are_allowed(): void
    {
        Post::factory()->count(2)->create([
            'old_url' => 'https://legacy.example.com/?p=malformed-duplicate',
        ]);

        $this->assertDatabaseCount('posts', 2);
    }

    public function test_scheduled_scope_returns_only_future_scheduled_posts(): void
    {
        $futureScheduled = Post::factory()->scheduled()->create();
        Post::factory()->create([
            'status' => PostStatus::Scheduled,
            'scheduled_at' => now()->subMinute(),
        ]);
        Post::factory()->create([
            'status' => PostStatus::Draft,
            'scheduled_at' => now()->addDay(),
        ]);

        $this->assertSame(
            [$futureScheduled->id],
            Post::query()->scheduled()->pluck('id')->all(),
        );
    }

    public function test_global_soft_delete_scope_excludes_deleted_records_from_post_scopes(): void
    {
        $published = Post::factory()->published()->create();
        $scheduled = Post::factory()->scheduled()->create();

        $published->delete();
        $scheduled->delete();

        $this->assertFalse(Post::query()->published()->whereKey($published->id)->exists());
        $this->assertFalse(Post::query()->scheduled()->whereKey($scheduled->id)->exists());
        $this->assertDatabaseCount('posts', 2);
    }

    public function test_system_managed_counters_and_deleted_at_are_not_mass_assignable(): void
    {
        $post = Post::query()->create([
            'title' => 'Protected lifecycle fields',
            'slug' => 'protected-lifecycle-fields',
            'content' => 'The model should retain database defaults.',
            'language' => 'en',
            'views_count' => 999,
            'likes_count' => 999,
            'deleted_at' => now(),
        ]);

        $post->refresh();

        $this->assertSame(0, $post->views_count);
        $this->assertSame(0, $post->likes_count);
        $this->assertNull($post->deleted_at);
    }

    public function test_factory_generates_realistic_news_content_and_dates(): void
    {
        $post = Post::factory()->withSeo()->create();

        $this->assertNotEmpty($post->title);
        $this->assertFalse(str_ends_with($post->title, '.'));
        $this->assertNotEmpty($post->excerpt);
        $this->assertStringStartsWith('<p>', $post->content);
        $this->assertStringContainsString('</p>', $post->content);
        $this->assertLessThanOrEqual(60, mb_strlen($post->meta_title));
        $this->assertLessThanOrEqual(160, mb_strlen($post->meta_description));
        $this->assertTrue($post->created_at->lessThanOrEqualTo($post->updated_at));
    }

    public function test_wordpress_factory_state_preserves_historical_publication_metadata(): void
    {
        $post = Post::factory()->importedFromWordPress()->create();

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->published_at->isPast());
        $this->assertTrue($post->created_at->equalTo($post->published_at));
        $this->assertSame($post->old_wp_id, $post->source_data['post_id']);
        $this->assertSame($post->old_url, $post->source_data['original_url']);
    }
}
