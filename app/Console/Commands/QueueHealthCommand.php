<?php

namespace App\Console\Commands;

use App\Jobs\QueueProbe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class QueueHealthCommand extends Command
{
    protected $signature = 'queue:health {--probe : Dispatch a lightweight probe job} {--json}';
    protected $description = 'Check queue configuration and optional Redis/probe readiness without exposing secrets.';

    public function handle(): int
    {
        $result = ['connection' => config('queue.default'), 'redis' => 'not-required', 'failed_jobs' => 'unknown', 'probe' => 'not-requested', 'status' => 'healthy'];
        try {
            $result['failed_jobs'] = (string) DB::table(config('queue.failed.table', 'failed_jobs'))->count();
        } catch (Throwable) { $result['failed_jobs'] = 'unavailable'; }
        if (config('queue.default') === 'redis' || $this->option('probe')) {
            try { Redis::connection(config('queue.connections.redis.connection', 'default'))->ping(); $result['redis'] = 'ok'; }
            catch (Throwable) { $result['redis'] = 'unavailable'; $result['status'] = 'degraded'; }
        }
        if ($this->option('probe')) {
            $id = bin2hex(random_bytes(8));
            QueueProbe::dispatch($id);
            $result['probe'] = ['id' => $id, 'queued' => true];
        }
        if ($this->option('json')) $this->line((string) json_encode($result, JSON_THROW_ON_ERROR));
        else foreach ($result as $key => $value) $this->line(str($key)->replace('_', ' ')->title().': '.(is_array($value) ? json_encode($value) : $value));
        return $result['status'] === 'healthy' ? self::SUCCESS : self::FAILURE;
    }
}
