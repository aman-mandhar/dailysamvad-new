<?php

namespace App\Import\DTOs;

final readonly class VerificationResult
{
    public function __construct(public ImportStatistics $statistics, public bool $valid) {}
}
