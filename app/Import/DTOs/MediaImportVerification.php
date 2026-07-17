<?php

namespace App\Import\DTOs;

class MediaImportVerification
{
    public int $missing = 0;

    public int $unreadable = 0;

    public int $unsupported = 0;

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'missing' => $this->missing,
            'unreadable' => $this->unreadable,
            'unsupported' => $this->unsupported,
        ];
    }
}
