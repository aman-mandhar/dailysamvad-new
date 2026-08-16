<?php

namespace App\Console\Commands;

use App\Services\Push\PushDeliveryRecoveryService;
use Illuminate\Console\Command;

class RecoverStuckPushDeliveriesCommand extends Command
{
    protected $signature = 'push:recover-stuck {--dry-run : Report candidates without changing or queueing them} {--limit= : Maximum deliveries to inspect}';

    protected $description = 'Safely requeue stale attempting push deliveries.';

    public function handle(PushDeliveryRecoveryService $recovery): int
    {
        $limit = $this->option('limit');
        $limit = $limit === null ? null : filter_var($limit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($this->option('limit') !== null && $limit === false) {
            $this->error('The --limit option must be a positive integer.');

            return self::INVALID;
        }

        $result = $recovery->recover((bool) $this->option('dry-run'), $limit ?: null);
        $this->line('Candidates: '.$result['candidates']);
        $this->line('Requeued: '.$result['requeued']);
        $this->line('Queue failures: '.$result['failed']);

        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
