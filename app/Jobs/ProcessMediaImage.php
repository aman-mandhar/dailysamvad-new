<?php
namespace App\Jobs;

use App\Models\Media;
use App\Services\ImageOptimizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessMediaImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3; public int $timeout = 120; public int $uniqueFor = 600;
    public function __construct(public readonly int $mediaId, public readonly bool $force = false) { $this->onQueue(config('image_optimization.queue', 'media')); }
    public function uniqueId(): string { return (string) $this->mediaId; }
    public function middleware(): array { return [new WithoutOverlapping('media-image-'.$this->mediaId)]; }
    public function backoff(): array { return [30, 120]; }
    public function handle(ImageOptimizationService $service): void { $media = Media::find($this->mediaId); if ($media) $service->process($media, $this->force); }
}
