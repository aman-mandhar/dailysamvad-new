<?php

namespace Tests\Feature;

use App\Jobs\PublishScheduledPost;
use App\Jobs\QueueProbe;
use App\Jobs\SubmitIndexNowUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_connections_and_reserved_redis_namespace_are_defined_without_activation(): void
    {
        $this->assertSame(env('QUEUE_CONNECTION', 'database'), config('queue.default'));
        $this->assertSame('redis', config('queue.connections.redis.driver'));
        $this->assertSame('queue', config('queue.connections.redis.connection'));
        $this->assertSame(3, (int) config('database.redis.queue.database'));
        $this->assertTrue(config('queue.connections.database.after_commit'));
        $this->assertTrue(config('queue.connections.redis.after_commit'));
    }

    public function test_jobs_have_named_queues_bounded_retries_and_scalar_payloads(): void
    {
        $publishing = new PublishScheduledPost(42);
        $external = new SubmitIndexNowUrls(['https://example.test/a']);
        $this->assertSame('publishing', $publishing->queue);
        $this->assertSame('external', $external->queue);
        $this->assertSame(3, $publishing->tries);
        $this->assertSame(30, $publishing->timeout);
        $this->assertSame(42, $publishing->postId);
    }

    public function test_queue_health_and_probe_are_safe_and_authority_remains_database(): void
    {
        $this->artisan('queue:health')->assertSuccessful()->expectsOutputToContain('Connection: '.config('queue.default'));
        Queue::fake();
        $this->artisan('queue:health', ['--probe' => true])->assertFailed();
        Queue::assertPushed(QueueProbe::class, fn (QueueProbe $job): bool => $job->queue === 'maintenance');
    }

    public function test_redis_queue_activation_is_not_forced_when_disabled(): void
    {
        $this->assertFalse((bool) config('queue_architecture.enabled'));
        $this->assertSame(env('QUEUE_CONNECTION', 'database'), config('queue.default'));
    }
}
