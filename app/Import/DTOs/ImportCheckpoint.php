<?php

namespace App\Import\DTOs;

use DateTimeImmutable;

final readonly class ImportCheckpoint
{
    public function __construct(
        public string $importId,
        public string $importer,
        public int|string $cursor,
        public ImportStatistics $statistics,
        public DateTimeImmutable $recordedAt,
    ) {}
}
