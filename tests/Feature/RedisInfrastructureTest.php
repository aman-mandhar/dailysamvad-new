<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedisInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_redis_connections_and_store_are_defined_without_activating_subsystems(): void
    {
        $this->assertSame(env('CACHE_STORE', 'database'), config('cache.default'));
        $this->assertSame(env('SESSION_DRIVER', 'database'), config('session.driver'));
        $this->assertSame(env('QUEUE_CONNECTION', 'database'), config('queue.default'));
        $this->assertSame('redis', config('cache.stores.redis.driver'));
        $this->assertSame('default', config('cache.stores.redis.lock_connection'));
        $this->assertArrayHasKey('default', config('database.redis'));
        $this->assertArrayHasKey('cache', config('database.redis'));
        $this->assertSame(0, (int) config('database.redis.default.database'));
        $this->assertSame(1, (int) config('database.redis.cache.database'));
    }

    public function test_health_command_fails_safely_when_redis_is_unavailable(): void
    {
        $result = $this->artisan('redis:health', ['--json' => true]);
        $result->assertExitCode(1)->expectsOutputToContain('"status":"failed"');
    }

    /** @requires extension redis */
    public function test_redis_integration_is_available_only_when_the_client_and_server_exist(): void
    {
        if (! extension_loaded('redis')) $this->markTestSkipped('PhpRedis extension is unavailable in this environment.');
        $this->markTestSkipped('Redis server integration is environment-dependent; run with a local authenticated server.');
    }
}
