<?php

namespace App\Import\DTOs;

final readonly class ImportProgress
{
    public function __construct(public int $processed = 0, public ?int $total = null) {}

    public function percentage(): ?float
    {
        return $this->total && $this->total > 0 ? round(($this->processed / $this->total) * 100, 2) : null;
    }
}
