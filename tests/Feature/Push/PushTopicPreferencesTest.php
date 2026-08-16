<?php

namespace Tests\Feature\Push;

use App\Jobs\Push\SendPushNotificationJob;
use App\Models\Category;
use App\Models\Post;
use App\Models\PushNotification;
use App\Models\PushSubscription;
use App\Models\PushTopic;
use App\Models\User;
use App\Services\Push\ManualPushNotificationService;
use App\Services\Push\PostPublishPushAutomation;
use App\Services\Push\PushAudienceResolver;
use App\Services\Push\PushSubscriptionService;
use App\Services\Push\PushTopicSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class PushTopicPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_topic_sync_creates_category_and_system_topics_idempotently(): void
    {
        $category = Category::factory()->create(['name' => 'Punjab', 'slug' => 'punjab']);
        $service = app(PushTopicSyncService::class);

        $first = $service->sync();
        $second = $service->sync();

        $this->assertGreaterThanOrEqual(2, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertDatabaseHas('push_topics', ['category_id' => $category->id, 'name' => 'Punjab', 'is_active' => true]);
        $this->assertDatabaseHas('push_topics', ['slug' => 'breaking-news', 'type' => 'system']);
    }

    public function test_category_rename_and_deactivation_preserve_topic_and_preferences(): void
    {
        $category = Category::factory()->create(['name' => 'Sports']);
        app(PushTopicSyncService::class)->sync();
        $topic = PushTopic::query()->whereBelongsTo($category)->firstOrFail();
        $subscription = PushSubscription::factory()->create(['preferences_configured_at' => now()]);
        $subscription->topics()->attach($topic);

        $category->update(['name' => 'खेल', 'is_active' => false]);
        app(PushTopicSyncService::class)->sync();

        $topic->refresh();
        $this->assertSame('खेल', $topic->name);
        $this->assertFalse($topic->is_active);
        $this->assertTrue($subscription->topics()->whereKey($topic->id)->exists());
    }

    public function test_guest_and_authenticated_devices_can_save_preferences_without_token_exposure(): void
    {
        $topic = PushTopic::factory()->create();
        $guest = PushSubscription::factory()->create();
        $user = User::factory()->create();
        $authenticated = PushSubscription::factory()->for($user)->create();

        foreach ([[$guest, null], [$authenticated, $user]] as [$subscription, $actingUser]) {
            if ($actingUser) {
                $this->actingAs($actingUser);
            } else {
                auth()->logout();
            }
            $response = $this->putJson('/push/preferences', [
                'token' => $subscription->token,
                'device_uuid' => $subscription->device_uuid,
                'topic_ids' => [$topic->id, $topic->id],
                'user_id' => 999999,
            ])->assertOk()->assertHeader('Cache-Control', 'no-store, private');

            $response->assertJsonMissing(['token' => $subscription->token]);
            $this->assertSame(1, $subscription->topics()->count());
            $this->assertNotNull($subscription->fresh()->preferences_configured_at);
        }
    }

    public function test_invalid_and_inactive_topics_are_rejected(): void
    {
        $subscription = PushSubscription::factory()->create();
        $inactive = PushTopic::factory()->create(['is_active' => false]);

        $this->putJson('/push/preferences', ['token' => $subscription->token, 'device_uuid' => $subscription->device_uuid, 'topic_ids' => [$inactive->id]])->assertNotFound();
        $this->putJson('/push/preferences', ['token' => $subscription->token, 'device_uuid' => $subscription->device_uuid, 'topic_ids' => [999999]])->assertNotFound();
    }

    public function test_explicit_zero_topics_is_distinct_from_legacy_behavior(): void
    {
        $topic = PushTopic::factory()->create();
        $legacy = PushSubscription::factory()->create();
        $zero = PushSubscription::factory()->create(['preferences_configured_at' => now()]);
        $resolver = app(PushAudienceResolver::class);

        $this->assertTrue($resolver->forTopics([$topic->id], true)->whereKey($legacy->id)->exists());
        $this->assertFalse($resolver->forTopics([$topic->id], true)->whereKey($zero->id)->exists());
        $this->assertTrue($resolver->allActive()->whereKey($zero->id)->exists());
    }

    public function test_post_audience_matches_any_topic_once_and_excludes_nonmatching_and_inactive(): void
    {
        [$sports, $punjab] = Category::factory()->count(2)->create();
        app(PushTopicSyncService::class)->sync();
        $sportsTopic = PushTopic::query()->where('category_id', $sports->id)->firstOrFail();
        $punjabTopic = PushTopic::query()->where('category_id', $punjab->id)->firstOrFail();
        $both = PushSubscription::factory()->create(['preferences_configured_at' => now()]);
        $both->topics()->attach([$sportsTopic->id, $punjabTopic->id]);
        $unrelated = PushSubscription::factory()->create(['preferences_configured_at' => now()]);
        $inactive = PushSubscription::factory()->create(['preferences_configured_at' => now(), 'is_active' => false]);
        $inactive->topics()->attach($sportsTopic);
        $legacy = PushSubscription::factory()->create();
        $post = Post::factory()->published()->create();
        $post->categories()->attach([$sports->id, $punjab->id]);

        $ids = app(PushAudienceResolver::class)->forPost($post)->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$both->id, $legacy->id], $ids);
        $this->assertNotContains($unrelated->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
        $this->assertCount(2, $ids);
    }

    public function test_breaking_topic_matches_breaking_posts_only(): void
    {
        app(PushTopicSyncService::class)->sync();
        $breaking = PushTopic::query()->where('slug', 'breaking-news')->firstOrFail();
        $subscription = PushSubscription::factory()->create(['preferences_configured_at' => now()]);
        $subscription->topics()->attach($breaking);
        $breakingPost = Post::factory()->published()->breaking()->create();
        $ordinaryPost = Post::factory()->published()->create();

        $this->assertTrue(app(PushAudienceResolver::class)->forPost($breakingPost)->whereKey($subscription->id)->exists());
        $this->assertFalse(app(PushAudienceResolver::class)->forPost($ordinaryPost)->whereKey($subscription->id)->exists());
    }

    public function test_manual_all_ignores_preferences_and_selected_topics_are_explicit_and_unique(): void
    {
        Queue::fake();
        $topic = PushTopic::factory()->create();
        $matching = PushSubscription::factory()->create(['preferences_configured_at' => now()]);
        $matching->topics()->attach($topic);
        $legacy = PushSubscription::factory()->create();
        $zero = PushSubscription::factory()->create(['preferences_configured_at' => now()]);

        $all = PushNotification::factory()->create(['target_type' => 'all']);
        $this->assertSame(3, app(ManualPushNotificationService::class)->send($all));

        $selected = PushNotification::factory()->create(['target_type' => 'topics']);
        $selected->topics()->attach($topic);
        $this->assertSame(1, app(ManualPushNotificationService::class)->recipientCount($selected));
        $this->assertSame(1, app(ManualPushNotificationService::class)->send($selected));
        Queue::assertPushed(SendPushNotificationJob::class, 4);
    }

    public function test_auto_publish_queues_only_the_resolved_unique_post_audience(): void
    {
        Queue::fake();
        $category = Category::factory()->create();
        app(PushTopicSyncService::class)->sync();
        $topic = PushTopic::query()->where('category_id', $category->id)->firstOrFail();
        $matching = PushSubscription::factory()->create(['preferences_configured_at' => now()]);
        $matching->topics()->attach($topic);
        PushSubscription::factory()->create(['preferences_configured_at' => now()]);
        PushSubscription::factory()->create();
        $post = Post::factory()->published()->create(['push_notified_at' => null]);
        $post->categories()->attach($category);
        config()->set('firebase.automation.enabled', true);

        $this->assertTrue(app(PostPublishPushAutomation::class)->dispatch($post->id));
        Queue::assertPushed(SendPushNotificationJob::class, 2);
    }

    public function test_preference_endpoint_cannot_modify_another_device(): void
    {
        $topic = PushTopic::factory()->create();
        $owner = PushSubscription::factory()->create();
        $other = PushSubscription::factory()->create();

        $this->putJson('/push/preferences', ['token' => $owner->token, 'device_uuid' => $other->device_uuid, 'topic_ids' => [$topic->id]])->assertNotFound();
        $this->assertSame(0, $owner->topics()->count());
        $this->assertSame(0, $other->topics()->count());
    }

    public function test_same_device_token_rotation_preserves_preferences(): void
    {
        $topic = PushTopic::factory()->create();
        $old = PushSubscription::factory()->create(['preferences_configured_at' => now()]);
        $old->topics()->attach($topic);
        $newToken = 'rotated-fcm-'.Str::random(180);

        $result = app(PushSubscriptionService::class)->register($newToken, null, ['device_uuid' => $old->device_uuid]);
        $new = $result['subscription'];

        $this->assertFalse($old->fresh()->is_active);
        $this->assertNotNull($new->preferences_configured_at);
        $this->assertTrue($new->topics()->whereKey($topic->id)->exists());
    }
}
