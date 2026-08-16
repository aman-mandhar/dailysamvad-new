<?php

namespace App\Console\Commands;

use App\Services\Push\PushTopicSyncService;
use Illuminate\Console\Command;

class SyncPushTopicsCommand extends Command
{
    protected $signature = 'push:sync-topics';

    protected $description = 'Synchronize active push topics with the existing news categories';

    public function handle(PushTopicSyncService $topics): int
    {
        $result = $topics->sync();
        $this->info("Push topics synchronized: {$result['created']} created, {$result['updated']} updated, {$result['deactivated']} deactivated.");

        return self::SUCCESS;
    }
}
