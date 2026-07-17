<?php

namespace App\Import\DTOs;

class SeoImportVerification
{
    public int $missingPost = 0;

    public int $seoImported = 0;

    public int $seoGenerated = 0;

    public int $seoMissing = 0;

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'missing_post' => $this->missingPost,
            'seo_imported' => $this->seoImported,
            'seo_generated' => $this->seoGenerated,
            'seo_missing' => $this->seoMissing,
        ];
    }
}
