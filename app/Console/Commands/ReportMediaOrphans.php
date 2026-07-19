<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Models\Post;
use Illuminate\Console\Command;

class ReportMediaOrphans extends Command
{
    protected $signature = 'media:report-orphans {--chunk=500} {--dry-run}';

    protected $description = 'Report non-destructive media orphan candidates';

    public function handle(): int
    {
        $candidates = 0;
        Media::query()->whereDoesntHave('featuredPosts')->orderBy('id')->chunkById(max(1, (int) $this->option('chunk')), function ($media) use (&$candidates): void {
            foreach ($media as $item) {
                if (Post::withTrashed()->where('featured_image', $item->path)->orWhere('content', 'like', '%'.$item->path.'%')->exists()) {
                    continue;
                }
                $candidates++;
                $this->line("Candidate {$item->id}: {$item->path}");
            }
        });
        $this->info("{$candidates} candidate(s). No files or records were deleted.");

        return self::SUCCESS;
    }
}
