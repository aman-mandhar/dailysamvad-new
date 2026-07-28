<?php

namespace Tests\Feature;

use App\Jobs\ProcessAnalyticsEvent;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class AnalyticsViewCountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('analytics.enabled', true);
        config()->set('analytics.beacon_enabled', true);
        Cache::flush();
        Queue::fake();
    }

    public function test_public_article_beacon_increments_view_count_immediately_and_queues_details(): void
    {
        $post = Post::factory()->published()->create(['views_count' => 0]);
        $eventId = (string) Str::uuid();

        $this->withHeader('User-Agent', 'Mozilla/5.0')
            ->postJson(route('analytics.beacon', $post), ['event_id' => $eventId])
            ->assertAccepted()
            ->assertCookie('ds_visitor');

        $this->assertSame(1, $post->refresh()->views_count);
        Queue::assertPushed(ProcessAnalyticsEvent::class, fn (ProcessAnalyticsEvent $job): bool => $job->postId === $post->id);

        $this->withHeader('User-Agent', 'Mozilla/5.0')
            ->postJson(route('analytics.beacon', $post), ['event_id' => $eventId])
            ->assertNoContent();

        $this->assertSame(1, $post->refresh()->views_count);
    }

    public function test_bot_and_unpublished_article_views_are_not_counted(): void
    {
        $published = Post::factory()->published()->create(['views_count' => 0]);
        $draft = Post::factory()->create(['views_count' => 0]);

        $this->withHeader('User-Agent', 'Googlebot/2.1')
            ->postJson(route('analytics.beacon', $published), ['event_id' => (string) Str::uuid()])
            ->assertAccepted();
        $this->withHeader('User-Agent', 'Mozilla/5.0')
            ->postJson(route('analytics.beacon', $draft), ['event_id' => (string) Str::uuid()])
            ->assertNoContent();

        $this->assertSame(0, $published->refresh()->views_count);
        $this->assertSame(0, $draft->refresh()->views_count);
    }
}
