<?php

namespace App\Import\DTOs;

class PostImportVerification
{
    public int $missingAuthor = 0;

    public int $missingCategory = 0;

    public int $draftWithoutCategory = 0;

    public int $categoryMappingFailure = 0;

    public int $missingTag = 0;

    public int $slugConflict = 0;

    public int $seoImported = 0;

    public int $seoGenerated = 0;

    public int $seoMissing = 0;

    public int $skippedByFilter = 0;

    public int $unsupportedStatus = 0;

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'missing_author' => $this->missingAuthor,
            'missing_category' => $this->missingCategory,
            'draft_without_category' => $this->draftWithoutCategory,
            'category_mapping_failure' => $this->categoryMappingFailure,
            'missing_tag' => $this->missingTag,
            'slug_conflict' => $this->slugConflict,
            'seo_imported' => $this->seoImported,
            'seo_generated' => $this->seoGenerated,
            'seo_missing' => $this->seoMissing,
            'skipped_by_filter' => $this->skippedByFilter,
            'unsupported_status' => $this->unsupportedStatus,
        ];
    }
}
