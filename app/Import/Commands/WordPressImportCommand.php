<?php

namespace App\Import\Commands;

use App\Import\Contracts\Logger;
use App\Import\DTOs\ImportContext;
use App\Import\DTOs\ImportProgress;
use App\Import\DTOs\ImportStatistics;
use App\Import\Importers\CategoryImporter;
use App\Import\Importers\PostImporter;
use App\Import\Importers\SeoImporter;
use App\Import\Importers\TagImporter;
use App\Import\Importers\UserImporter;
use App\Import\Importers\WordPressMediaImporter;
use App\Import\Services\ImportReportStore;
use App\Import\Support\ImportMode;
use Illuminate\Console\Command;

class WordPressImportCommand extends Command
{
    protected $signature = 'import:wordpress
        {--dry-run : Report changes without writing data}
        {--resume : Continue from the last successful checkpoint}
        {--only=* : Import users, categories, tags, posts, media, or SEO}
        {--chunk= : Records per transaction}
        {--limit= : Maximum records (posts default to 100)}
        {--offset=0 : Skip this many selected posts}
        {--ids=* : Import specific WordPress post or attachment IDs}
        {--order= : Select latest or oldest posts}
        {--status=publish : Post status: publish, draft, pending, future, private, or all}';

    protected $description = 'Import WordPress users, taxonomy, pilot posts, and media';

    public function handle(
        Logger $logger,
        UserImporter $users,
        CategoryImporter $categories,
        TagImporter $tags,
        PostImporter $posts,
        WordPressMediaImporter $media,
        SeoImporter $seo,
        ImportReportStore $reports,
    ): int {
        $chunk = (int) ($this->option('chunk') ?: config('import.chunk_size', 500));
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $offset = (int) $this->option('offset');
        $order = (string) ($this->option('order') ?: config('import.pilot.order', 'latest'));
        $status = strtolower((string) $this->option('status'));
        $ids = collect($this->option('ids'))->flatMap(fn (string $value) => explode(',', $value))
            ->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        if ($chunk < 1 || ($limit !== null && $limit < 1) || $offset < 0 || collect($ids)->contains(fn ($id) => $id < 1)) {
            $this->error('The chunk, limit and IDs must be positive; offset cannot be negative.');

            return self::INVALID;
        }

        if (! in_array($order, ['latest', 'oldest'], true)) {
            $this->error('The --order option must be latest or oldest.');

            return self::INVALID;
        }

        if (! in_array($status, ['publish', 'draft', 'pending', 'future', 'private', 'all'], true)) {
            $this->error('The --status option supports: publish, draft, pending, future, private, all.');

            return self::INVALID;
        }

        $selected = collect($this->option('only'))
            ->flatMap(fn (string $value) => explode(',', $value))->filter()->values()->all();
        $importers = collect([$users, $categories, $tags, $posts, $media, $seo])->keyBy->key();

        if ($selected !== [] && collect($selected)->diff($importers->keys())->isNotEmpty()) {
            $this->error('The --only option supports: users, categories, tags, posts, media, seo.');

            return self::INVALID;
        }

        ini_set('memory_limit', (string) config('import.memory_limit', '512M'));
        set_time_limit((int) config('import.timeouts.command', 3600));

        $context = new ImportContext(
            importId: 'wordpress', source: 'wordpress', progress: new ImportProgress,
            chunk: $chunk, mode: $this->option('dry-run') || config('import.dry_run') ? ImportMode::DryRun : ImportMode::Live,
            statistics: new ImportStatistics, resume: (bool) $this->option('resume') || config('import.resume'),
            only: $selected, limit: $limit, offset: $offset, ids: $ids, order: $order, status: $status,
        );

        $totals = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'duplicates' => 0];
        // Copying files is intentionally opt-in; a command without --only retains the earlier data-import behavior.
        $chosen = $selected === [] ? $importers->except(['media', 'seo']) : $importers->only($selected);

        foreach ($chosen as $importer) {
            $result = $importer->import($context);
            foreach ($totals as $key => $value) {
                $totals[$key] += $result->statistics->{$key};
            }
        }

        $statistics = new ImportStatistics(...$totals);
        $logger->summary($statistics);
        $this->table(array_keys($totals), [array_values($totals)]);
        if ($chosen->has('posts')) {
            $verification = $posts->verification()->toArray();
            $this->newLine();
            $this->line('Pilot verification');
            $this->table(array_keys($verification), [array_values($verification)]);
        }
        if ($chosen->has('media')) {
            $verification = $media->verification()->toArray();
            $this->newLine();
            $this->line('Media verification');
            $this->table(array_keys($verification), [array_values($verification)]);
        }
        if ($chosen->has('seo')) {
            $verification = $seo->verification()->toArray();
            $this->newLine();
            $this->line('SEO verification');
            $this->table(array_keys($verification), [array_values($verification)]);
        }
        $reports->recordRun([
            'import_id' => $context->importId,
            'mode' => $context->mode->value,
            'importers' => $chosen->keys()->values()->all(),
            'statistics' => $statistics->toArray(),
            'verification' => [
                'posts' => $chosen->has('posts') ? $posts->verification()->toArray() : null,
                'media' => $chosen->has('media') ? $media->verification()->toArray() : null,
                'seo' => $chosen->has('seo') ? $seo->verification()->toArray() : null,
            ],
            'resume' => $context->resume,
            'dry_run' => $context->mode === ImportMode::DryRun,
            'status' => $context->status,
            'completed_at' => now()->toIso8601String(),
        ]);
        $this->info($context->mode === ImportMode::DryRun ? 'Dry run completed; no destination data or checkpoints were written.' : 'WordPress import completed.');

        return self::SUCCESS;
    }
}
