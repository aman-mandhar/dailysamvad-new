<?php

namespace App\Import\DTOs;

use App\Import\Support\ImportMode;

final readonly class ImportContext
{
    /** @param array<int, string> $only */
    public function __construct(
        public string $importId,
        public string $source,
        public ImportProgress $progress,
        public int $chunk,
        public ImportMode $mode,
        public ImportStatistics $statistics,
        public bool $resume = false,
        public array $only = [],
        public ?int $limit = null,
        public int $offset = 0,
        /** @var array<int, int> */
        public array $ids = [],
        public string $order = 'latest',
        public string $status = 'publish',
    ) {}
}
