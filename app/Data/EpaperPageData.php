<?php

namespace App\Data;

use App\Models\Post;
use Illuminate\Support\Collection;

final readonly class EpaperPageData
{
    /** @param Collection<int, ArticleContentBlockData> $contentBlocks */
    public function __construct(
        public Post $post,
        public Collection $contentBlocks,
        public string $canonicalUrl,
    ) {}
}
