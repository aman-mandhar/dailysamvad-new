<?php

namespace Tests\Feature\Push;

use App\Enums\PushNotificationStatus;
use App\Filament\Resources\PushNotifications\PushNotificationResource;
use App\Jobs\Push\SendPushNotificationJob;
use App\Models\Post;
use App\Models\PushNotification;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Push\ManualPushNotificationService;
use App\Services\Push\PushNotificationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class FilamentPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_authorized_admin_can_access_resource_and_reporter_cannot(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $reporter = User::factory()->create()->assignRole('reporter');

        $this->actingAs($admin)->get(PushNotificationResource::getUrl('index'))->assertOk();
        $this->actingAs($reporter)->get(PushNotificationResource::getUrl('index'))->assertForbidden();
    }

    public function test_reporter_cannot_send_and_admin_has_explicit_send_permission(): void
    {
        $notification = PushNotification::factory()->create();
        $reporter = User::factory()->create()->assignRole('reporter');
        $admin = User::factory()->create()->assignRole('admin');

        $this->assertFalse(Gate::forUser($reporter)->allows('send', $notification));
        $this->assertTrue(Gate::forUser($admin)->allows('send', $notification));

        $this->actingAs($reporter);
        $this->expectException(AuthorizationException::class);
        Gate::authorize('send', $notification);
    }

    public function test_creating_and_editing_a_draft_never_dispatches(): void
    {
        Queue::fake();
        $notification = PushNotification::query()->create([
            'title' => 'Draft title',
            'body' => 'Draft body',
        ]);
        $notification->update(['title' => 'Edited draft title']);

        Queue::assertNothingPushed();
        $this->assertSame(PushNotificationStatus::Draft, $notification->fresh()->status);
    }

    public function test_send_queues_each_active_subscription_exactly_once(): void
    {
        Queue::fake();
        PushSubscription::factory()->count(3)->create(['is_active' => true]);
        PushSubscription::factory()->create(['is_active' => false]);
        $notification = PushNotification::factory()->create([
            'image_url' => 'https://example.com/image.jpg',
            'target_url' => 'https://example.com/story',
        ]);

        $count = app(ManualPushNotificationService::class)->send($notification);

        $this->assertSame(3, $count);
        Queue::assertPushed(SendPushNotificationJob::class, 3);
        $notification->refresh();
        $this->assertSame(PushNotificationStatus::Sent, $notification->status);
        $this->assertSame(3, $notification->recipient_count);
        $this->assertNotNull($notification->queued_at);
        $this->assertNotNull($notification->sent_at);
    }

    public function test_duplicate_send_and_sent_record_edit_are_blocked(): void
    {
        Queue::fake();
        PushSubscription::factory()->create(['is_active' => true]);
        $notification = PushNotification::factory()->create();
        $service = app(ManualPushNotificationService::class);
        $service->send($notification);

        try {
            $service->send($notification);
            $this->fail('A sent notification was accepted a second time.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already', $exception->getMessage());
        }

        Queue::assertPushed(SendPushNotificationJob::class, 1);
        $admin = User::factory()->create()->assignRole('admin');
        $this->assertFalse(Gate::forUser($admin)->allows('update', $notification->fresh()));
        $this->assertFalse(Gate::forUser($admin)->allows('send', $notification->fresh()));
    }

    public function test_zero_subscribers_does_not_change_draft_status(): void
    {
        Queue::fake();
        $notification = PushNotification::factory()->create();

        $this->expectException(RuntimeException::class);

        try {
            app(ManualPushNotificationService::class)->send($notification);
        } finally {
            Queue::assertNothingPushed();
            $this->assertSame(PushNotificationStatus::Draft, $notification->fresh()->status);
        }
    }

    public function test_optional_image_and_url_are_snapshotted_in_standalone_message(): void
    {
        $notification = PushNotification::factory()->create([
            'post_id' => null,
            'image_url' => 'https://example.com/alert.jpg',
            'target_url' => 'https://example.com/alerts/latest',
        ]);

        $message = app(ManualPushNotificationService::class)->message($notification);

        $this->assertSame($notification->title, $message->title);
        $this->assertSame($notification->body, $message->body);
        $this->assertSame('https://example.com/alert.jpg', $message->image);
        $this->assertSame('https://example.com/alerts/latest', $message->url);
        $this->assertSame('manual', $message->data['type']);
    }

    public function test_unsafe_url_schemes_are_rejected_before_dispatch(): void
    {
        Queue::fake();
        PushSubscription::factory()->create(['is_active' => true]);
        $notification = PushNotification::factory()->create(['target_url' => 'javascript:alert(1)']);

        $this->expectException(ValidationException::class);

        try {
            app(ManualPushNotificationService::class)->send($notification);
        } finally {
            Queue::assertNothingPushed();
            $this->assertSame(PushNotificationStatus::Draft, $notification->fresh()->status);
        }
    }

    public function test_subscriber_count_uses_active_subscriptions_only(): void
    {
        PushSubscription::factory()->count(2)->create(['is_active' => true]);
        PushSubscription::factory()->count(3)->create(['is_active' => false]);

        $this->assertSame(2, app(ManualPushNotificationService::class)->activeSubscriberCount());
    }

    public function test_fan_out_failure_marks_record_failed_without_firebase_call(): void
    {
        PushSubscription::factory()->create(['is_active' => true]);
        $notification = PushNotification::factory()->create();
        $this->mock(PushNotificationService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('queueToActiveSubscriptions')->once()->andThrow(new RuntimeException('queue unavailable'));
        });

        $this->expectException(RuntimeException::class);

        try {
            app(ManualPushNotificationService::class)->send($notification);
        } finally {
            $notification->refresh();
            $this->assertSame(PushNotificationStatus::Failed, $notification->status);
            $this->assertSame('Broadcast fan-out could not be initiated.', $notification->failure_message);
            $this->assertNotNull($notification->failed_at);
            $this->assertNull($notification->sent_at);
        }
    }

    public function test_post_prefill_reuses_post_push_message_factory_snapshot(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'पंजाबी और हिंदी समाचार',
            'excerpt' => '<p>यह एक साफ और छोटा सारांश है।</p>',
            'featured_image' => 'https://example.com/news.jpg',
        ]);

        $prefill = PushNotificationResource::prefillFromPost($post);

        $this->assertSame('पंजाबी और हिंदी समाचार', $prefill['title']);
        $this->assertSame('यह एक साफ और छोटा सारांश है।', $prefill['body']);
        $this->assertSame('https://example.com/news.jpg', $prefill['image_url']);
        $this->assertSame($post->publicUrl(), $prefill['target_url']);
    }
}
