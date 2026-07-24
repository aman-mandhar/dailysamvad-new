<?php
namespace App\Console\Commands;
use App\Models\Media;
use App\Services\ImageOptimizationService;
use Illuminate\Console\Command;
class ImageAuditCommand extends Command
{
    protected $signature = 'images:audit {--json}'; protected $description = 'Audit media sources and encoder capabilities without modifying files';
    public function handle(ImageOptimizationService $service): int { $data = ['media' => Media::count(), 'missing' => Media::whereNotNull('missing_at')->count(), 'capabilities' => $service->capabilities(), 'presets' => config('image_optimization.presets')]; $this->line($this->option('json') ? json_encode($data, JSON_UNESCAPED_SLASHES) : print_r($data, true)); return self::SUCCESS; }
}
