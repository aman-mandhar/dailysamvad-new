<?php

namespace App\Import\DTOs;

final readonly class ImportResult
{
    public function __construct(public ImportStatistics $statistics, public bool $completed) {}
}
