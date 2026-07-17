<?php

namespace App\Import\Support;

use App\Import\DTOs\ImportStatistics;

class StatisticsCounter
{
    public int $imported = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $duplicates = 0;

    public int $failed = 0;

    public function statistics(): ImportStatistics
    {
        return new ImportStatistics($this->imported, $this->updated, $this->skipped, $this->failed, $this->duplicates);
    }
}
