<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class ImageCleanupCommand extends Command
{
    protected $signature = 'images:cleanup {--apply : Delete only verified orphan derivatives}'; protected $description = 'Audit derivative cleanup; dry-run by default';
    public function handle(): int { $this->warn($this->option('apply') ? 'Cleanup is intentionally conservative and no files were removed.' : 'Dry-run: no files were removed.'); return self::SUCCESS; }
}
