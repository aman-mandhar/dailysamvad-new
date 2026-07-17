<?php

namespace App\Import\Importers;

use App\Import\Contracts\CheckpointRepository;
use App\Import\Contracts\Importer;
use App\Import\Contracts\Logger;
use App\Import\DTOs\ImportCheckpoint;
use App\Import\DTOs\ImportContext;
use App\Import\DTOs\ImportResult;
use App\Import\Services\WordPressConnection;
use App\Import\Support\ImportMode;
use App\Import\Support\StatisticsCounter;
use DateTimeImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Throwable;

abstract class AbstractWordPressImporter implements Importer
{
    public function __construct(
        protected readonly WordPressConnection $source,
        protected readonly CheckpointRepository $checkpoints,
        protected readonly Logger $logger,
        protected readonly DatabaseManager $database,
    ) {}

    public function import(ImportContext $context): ImportResult
    {
        $counter = new StatisticsCounter;
        $cursor = $context->resume ? (int) ($this->checkpoints->latest($context->importId, $this->key())?->cursor ?? 0) : 0;
        $processed = 0;
        $started = microtime(true);

        $this->logger->info("Starting {$this->key()} import.", $this->metrics($context, $cursor));

        while ($context->limit === null || $processed < $context->limit) {
            $take = min($context->chunk, $context->limit === null ? $context->chunk : $context->limit - $processed);
            $records = $this->sourceRecords($cursor, $take);

            if ($records->isEmpty()) {
                break;
            }

            $chunkStarted = microtime(true);

            try {
                $work = function () use ($records, $counter, $context): void {
                    foreach ($records as $record) {
                        $this->processRecord($record, $counter, $context->mode === ImportMode::DryRun);
                    }
                };

                $context->mode === ImportMode::DryRun ? $work() : $this->database->connection()->transaction($work);
            } catch (Throwable $exception) {
                $counter->failed += $records->count();
                $this->logger->error("{$this->key()} chunk rolled back.", [
                    ...$this->metrics($context, $cursor), 'error' => $exception->getMessage(),
                ]);
                throw $exception;
            }

            $cursor = (int) $records->last()->source_id;
            $processed += $records->count();

            if ($context->mode === ImportMode::Live) {
                $this->checkpoints->store(new ImportCheckpoint(
                    $context->importId, $this->key(), $cursor, $counter->statistics(), new DateTimeImmutable,
                ));
            }

            $this->logger->info("Completed {$this->key()} chunk.", [
                ...$this->metrics($context, $cursor), 'records' => $records->count(),
                'seconds' => round(microtime(true) - $chunkStarted, 4), ...$counter->statistics()->toArray(),
            ]);
        }

        $this->afterChunks($context, $counter);
        $statistics = $counter->statistics();
        $this->logger->success("Completed {$this->key()} import.", [
            'seconds' => round(microtime(true) - $started, 4), 'memory_mb' => $this->memoryMb(), ...$statistics->toArray(),
        ]);

        return new ImportResult($statistics, true);
    }

    abstract protected function sourceRecords(int $cursor, int $limit): Collection;

    abstract protected function processRecord(object $record, StatisticsCounter $counter, bool $dryRun): void;

    protected function afterChunks(ImportContext $context, StatisticsCounter $counter): void {}

    /** @return array<string, mixed> */
    private function metrics(ImportContext $context, int $cursor): array
    {
        return ['import_id' => $context->importId, 'mode' => $context->mode->value, 'cursor' => $cursor, 'memory_mb' => $this->memoryMb()];
    }

    private function memoryMb(): float
    {
        return round(memory_get_usage(true) / 1048576, 2);
    }
}
