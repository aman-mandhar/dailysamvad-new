<?php
namespace App\Console\Commands;
use App\Jobs\ProcessMediaImage;
use App\Models\Media;
use Illuminate\Console\Command;
class ImageProcessCommand extends Command
{
    protected $signature = 'images:process {--id=} {--limit=10} {--sync} {--force}'; protected $description = 'Queue or synchronously process a bounded media sample';
    public function handle(): int { $q = Media::query()->whereNull('missing_at')->when($this->option('id'), fn ($q, $id) => $q->whereKey((int) $id))->limit((int) $this->option('limit')); $count=0; foreach ($q->get() as $media) { $this->option('sync') ? app(\App\Services\ImageOptimizationService::class)->process($media, (bool)$this->option('force')) : ProcessMediaImage::dispatch($media->id, (bool)$this->option('force')); $count++; } $this->info("Processed/queued {$count} media item(s)."); return self::SUCCESS; }
}
