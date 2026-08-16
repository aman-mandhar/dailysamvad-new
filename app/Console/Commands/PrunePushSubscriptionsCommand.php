<?php

namespace App\Console\Commands;

use App\Services\Push\PushSubscriptionCleanupService;
use Illuminate\Console\Command;

class PrunePushSubscriptionsCommand extends Command
{
    protected $signature = 'push:prune-subscriptions {--dry-run : Report candidates without deleting them} {--limit= : Maximum inactive subscriptions to delete}';

    protected $description = 'Conservatively prune old inactive push subscriptions while preserving delivery analytics.';

    public function handle(PushSubscriptionCleanupService $cleanup): int
    {
        $limit = $this->option('limit');
        $limit = $limit === null ? null : filter_var($limit, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($this->option('limit') !== null && $limit === false) {
            $this->error('The --limit option must be a positive integer.');

            return self::INVALID;
        }

        $result = $cleanup->prune((bool) $this->option('dry-run'), $limit ?: null);
        $this->line('Cutoff: '.$result['cutoff']);
        $this->line('Candidates: '.$result['candidates']);
        $this->line('Deleted: '.$result['deleted']);

        return self::SUCCESS;
    }
}
