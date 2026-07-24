<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class QueueProbe implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 15;

    public function __construct(public readonly string $probeId)
    {
        $this->onQueue(config('queue_architecture.probe_queue', 'maintenance'));
    }

    public function handle(): void
    {
        Cache::put('queue:probe:'.$this->probeId, ['completed_at' => now()->toIso8601String()], 300);
    }
}
