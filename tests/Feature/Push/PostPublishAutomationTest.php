<?php

namespace Tests\Feature\Push;

use App\Enums\PostStatus;
use App\Events\PostPublished;
use App\Jobs\Push\SendPushNotificationJob;
use App\Listeners\SendPostPublishedPush;
use App\Models\Post;
use App\Models\PushSubscription;
use App\Services\Push\PostPublishPushAutomation;
use App\Services\Push\PostPushMessageFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class PostPublishAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('firebase.automation.enabled', true);
    }

    public function test_draft_pending_and_non_public_saves_do_not_emit_publish_event(): void
    {
        Event::fake([PostPublished::class]);
        $draft = Post::factory()->create();
        $draft->update(['title' => 'Draft edit']);
        $draft->update(['status' => PostStatus::PendingReview]);
        $draft->update(['content' => '<p>Review edit</p>']);

        Event::assertNotDispatched(PostPublished::class);
    }

    public function test_first_real_publish_emits_once_but_published_edits_do_not(): void
    {
        Event::fake([PostPublished::class]);
        $post = Post::factory()->create(['status' => PostStatus::Approved]);
        $post->update(['status' => PostStatus::Published, 'published_at' => now()]);
        $post->update(['title' => 'Updated after publication', 'meta_description' => 'SEO edit']);

        Event::assertDispatchedTimes(PostPublished::class, 1);
    }

    public function test_scheduled_post_does_not_emit_early_but_emits_when_due_transition_occurs(): void
    {
        Event::fake([PostPublished::class]);
        $post = Post::factory()->scheduled()->create();
        $post->update(['title' => 'Scheduled edit']);
        Event::assertNotDispatched(PostPublished::class);

        $post->update([
            'status' => PostStatus::Published,
            'published_at' => now(),
            'scheduled_at' => null,
        ]);
        Event::assertDispatchedTimes(PostPublished::class, 1);
    }

    public function test_imported_historical_post_never_emits_automatic_publish_event(): void
    {
        Event::fake([PostPublished::class]);
        $post = Post::factory()->importedFromWordPress()->create();
        $post->update(['status' => PostStatus::Draft, 'published_at' => null]);
        $post->update(['status' => PostStatus::Published, 'published_at' => now()]);

        Event::assertNotDispatched(PostPublished::class);
    }

    public function test_enabled_automation_queues_active_subscriptions_once_and_republish_does_not_repeat(): void
    {
        Queue::fake();
        config()->set('firebase.automation.enabled', false);
        PushSubscription::factory()->count(2)->create();
        PushSubscription::factory()->create(['is_active' => false]);
        $post = Post::factory()->published()->create(['push_notified_at' => null]);
        config()->set('firebase.automation.enabled', true);
        $automation = app(PostPublishPushAutomation::class);

        $this->assertTrue($automation->dispatch($post->getKey()));
        $this->assertFalse($automation->dispatch($post->getKey()));

        Queue::assertPushed(SendPushNotificationJob::class, 2);
        $this->assertNotNull($post->fresh()->push_notified_at);

        $post->update(['status' => PostStatus::Draft, 'published_at' => null]);
        $post->update(['status' => PostStatus::Published, 'published_at' => now()]);
        $this->assertFalse($automation->dispatch($post->getKey()));
        Queue::assertPushed(SendPushNotificationJob::class, 2);
    }

    public function test_disabled_automation_and_no_subscribers_are_safe(): void
    {
        Queue::fake();
        config()->set('firebase.automation.enabled', false);
        $post = Post::factory()->published()->create(['push_notified_at' => null]);
        $this->assertFalse(app(PostPublishPushAutomation::class)->dispatch($post->getKey()));
        $this->assertNull($post->fresh()->push_notified_at);

        config()->set('firebase.automation.enabled', true);
        $this->assertTrue(app(PostPublishPushAutomation::class)->dispatch($post->getKey()));
        $this->assertNotNull($post->fresh()->push_notified_at);
        Queue::assertNothingPushed();
    }

    public function test_message_factory_maps_unicode_plain_text_canonical_url_and_image(): void
    {
        config()->set('app.url', 'https://news.example.test');
        url()->forceRootUrl('https://news.example.test');
        $post = Post::factory()->published()->create([
            'title' => 'ਪੰਜਾਬ हिंदी English',
            'excerpt' => '<script>alert(1)</script><p>ਸਾਫ਼ &amp; छोटा समाचार</p>',
            'featured_image' => 'media/library/push.jpg',
        ]);

        $message = app(PostPushMessageFactory::class)->make($post);

        $this->assertSame('ਪੰਜਾਬ हिंदी English', $message->title);
        $this->assertSame('ਸਾਫ਼ & छोटा समाचार', $message->body);
        $this->assertSame($post->publicUrl(), $message->url);
        $this->assertMatchesRegularExpression('#^https?://news\\.example\\.test/#', $message->image);
        $this->assertStringEndsWith('/storage/media/library/push.jpg', $message->image);
        $this->assertSame(['type' => 'post', 'entity_id' => (string) $post->getKey()], $message->data);

        $withoutImage = Post::factory()->published()->create(['featured_image' => null]);
        $this->assertNull(app(PostPushMessageFactory::class)->make($withoutImage)->image);
    }

    public function test_rolled_back_publish_does_not_dispatch_after_commit_event(): void
    {
        Event::fake([PostPublished::class]);
        $post = Post::factory()->create(['status' => PostStatus::Approved]);

        try {
            DB::transaction(function () use ($post): void {
                $post->update(['status' => PostStatus::Published, 'published_at' => now()]);
                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
        }

        Event::assertNotDispatched(PostPublished::class);
        $this->assertSame(PostStatus::Approved, $post->fresh()->status);
    }

    public function test_listener_swallows_unexpected_engine_failure_after_publication(): void
    {
        $automation = $this->mock(PostPublishPushAutomation::class);
        $automation->shouldReceive('dispatch')->once()->andThrow(new RuntimeException('engine unavailable'));

        (new SendPostPublishedPush($automation))->handle(new PostPublished(123));

        $this->assertTrue(true);
    }
}
