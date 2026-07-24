<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Throwable;

class RedisHealthCommand extends Command
{
    protected $signature = 'redis:health {--json : Emit machine-readable output}';
    protected $description = 'Check Redis client connectivity, cache operations, and atomic locks without exposing secrets.';

    public function handle(): int
    {
        $results = [
            'client' => config('database.redis.client'),
            'connection' => 'unverified',
            'ping' => 'skipped',
            'cache_write' => 'skipped',
            'cache_read' => 'skipped',
            'cache_forget' => 'skipped',
            'lock' => 'skipped',
            'status' => 'failed',
        ];
        $key = 'health:'.config('app.env').':'.bin2hex(random_bytes(8));
        try {
            $connection = Redis::connection('default');
            $connection->ping();
            $results['connection'] = 'ok';
            $results['ping'] = 'ok';
            $store = Cache::store('redis');
            $store->put($key, 'ok', 30);
            $results['cache_write'] = 'ok';
            $results['cache_read'] = $store->get($key) === 'ok' ? 'ok' : 'failed';
            $store->forget($key);
            $results['cache_forget'] = $store->get($key) === null ? 'ok' : 'failed';
            $lock = $store->lock($key.':lock', 10);
            $acquired = $lock->get();
            $results['lock'] = $acquired ? 'ok' : 'failed';
            if ($acquired) $lock->release();
            $results['status'] = collect($results)->except(['client', 'connection', 'status'])->every(fn (string $value): bool => in_array($value, ['ok'], true)) ? 'healthy' : 'failed';
        } catch (Throwable $exception) {
            $results['error'] = $exception::class;
            $results['message'] = 'Redis is unavailable or misconfigured.';
        } finally {
            try { Cache::store('redis')->forget($key); } catch (Throwable) { /* best effort cleanup */ }
        }
        if ($this->option('json')) $this->line((string) json_encode($results, JSON_THROW_ON_ERROR));
        else foreach ($results as $label => $value) $this->line(str($label)->replace('_', ' ')->title().': '.$value);
        return $results['status'] === 'healthy' ? self::SUCCESS : self::FAILURE;
    }
}
