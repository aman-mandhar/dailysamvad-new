<?php

namespace App\Import\Contracts;

use App\Import\DTOs\ImportCheckpoint;

interface CheckpointRepository
{
    public function latest(string $importId, string $importer): ?ImportCheckpoint;

    public function store(ImportCheckpoint $checkpoint): void;
}
