<?php

namespace App\Import\Services;

use App\Import\Contracts\CheckpointRepository;
use App\Import\DTOs\ImportCheckpoint;
use App\Import\DTOs\ImportStatistics;
use DateTimeImmutable;
use Illuminate\Filesystem\FilesystemManager;

class FileCheckpointRepository implements CheckpointRepository
{
    public function __construct(private readonly FilesystemManager $filesystems) {}

    public function latest(string $importId, string $importer): ?ImportCheckpoint
    {
        $disk = $this->filesystems->disk(config('import.checkpoint.disk', 'local'));
        $path = $this->path($importId, $importer);

        if (! $disk->exists($path)) {
            return null;
        }

        $data = json_decode($disk->get($path), true, flags: JSON_THROW_ON_ERROR);

        return new ImportCheckpoint(
            $data['import_id'], $data['importer'], $data['cursor'],
            new ImportStatistics(...$data['statistics']), new DateTimeImmutable($data['recorded_at']),
        );
    }

    public function store(ImportCheckpoint $checkpoint): void
    {
        $this->filesystems->disk(config('import.checkpoint.disk', 'local'))->put(
            $this->path($checkpoint->importId, $checkpoint->importer),
            json_encode([
                'import_id' => $checkpoint->importId,
                'importer' => $checkpoint->importer,
                'cursor' => $checkpoint->cursor,
                'statistics' => $checkpoint->statistics->toArray(),
                'recorded_at' => $checkpoint->recordedAt->format(DATE_ATOM),
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        );
    }

    private function path(string $importId, string $importer): string
    {
        $safe = fn (string $value): string => preg_replace('/[^A-Za-z0-9_.-]/', '_', $value) ?: 'import';

        return trim(config('import.checkpoint.path', 'imports/checkpoints'), '/').'/'.$safe($importId).'/'.$safe($importer).'.json';
    }
}
