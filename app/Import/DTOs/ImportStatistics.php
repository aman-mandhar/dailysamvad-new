<?php

namespace App\Import\DTOs;

final readonly class ImportStatistics
{
    public function __construct(
        public int $imported = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public int $failed = 0,
        public int $duplicates = 0,
    ) {}

    /** @return array<string, int> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
