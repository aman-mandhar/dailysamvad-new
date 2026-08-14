<?php

namespace App\Queries;

use App\Data\EpaperPageData;
use App\Models\Post;
use App\Services\ArticleContentComposer;

class EpaperPageQuery
{
    public function __construct(private readonly ArticleContentComposer $contentComposer) {}

    public function find(string $slug): EpaperPageData
    {
        $post = Post::query()
            ->published()
            ->with([
                'author:id,name,username,slug',
                'primaryCategory:id,name,slug',
                'featuredMedia:id,disk,path,alt_text,mime_type,width,height,missing_at,metadata',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        return new EpaperPageData(
            post: $post,
            contentBlocks: $this->contentComposer->compose($post->content)->where('type', 'html')->values(),
            canonicalUrl: $post->publicUrl(),
        );
    }
}
