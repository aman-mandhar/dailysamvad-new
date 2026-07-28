<?php

namespace App\Queries;

use App\Data\ArticlePageData;
use App\Models\Post;
use App\Services\ArticleContentComposer;
use App\Services\CacheQueryService;
use App\Support\PostSeoData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ArticlePageQuery
{
    public function __construct(
        private readonly SidebarQuery $sidebar,
        private readonly ArticleContentComposer $contentComposer,
    ) {}

    public function find(string $slug): ArticlePageData
    {
        if (config('cache_architecture.enabled') && config('cache_architecture.query')) {
            return app(CacheQueryService::class)->remember('query', 'article', 'public', $slug, (int) config('cache_architecture.ttls.medium', 1800), fn (): ArticlePageData => $this->uncachedFind($slug));
        }

        return $this->uncachedFind($slug);
    }

    private function uncachedFind(string $slug): ArticlePageData
    {
        $post = Post::query()
            ->published()
            ->with([
                'author:id,name,username,slug,bio,avatar_path,designation,email,mobile_number,is_public,x_url',
                'primaryCategory:id,name,slug',
                'categories:id,name,slug',
                'tags:id,name,slug',
                'featuredMedia:id,disk,path,alt_text,mime_type,width,height,missing_at,metadata',
            ])
            ->where(function (Builder $query) use ($slug): void {
                $query->where('slug', $slug)
                    ->orWhereRaw("REPLACE(TRIM(slug), ' ', '-') = ?", [Post::normalizeSlug($slug)]);
            })
            ->orderByRaw('CASE WHEN slug = ? THEN 0 ELSE 1 END', [$slug])
            ->firstOrFail();

        $canonicalUrl = $post->effectiveCanonicalUrl() ?? $post->publicUrl();
        $category = $post->primaryCategory->first();
        $breadcrumbs = collect([
            ['label' => 'Home', 'url' => route('home'), 'current' => false],
            ...($category ? [['label' => $category->name, 'url' => route('categories.show', $category->slug), 'current' => false]] : []),
            ['label' => $post->title, 'url' => $canonicalUrl, 'current' => true],
        ]);
        $seoDescription = $post->effectiveMetaDescription();
        $sidebar = $this->sidebar->forContext('article');
        $inlineAdvertisements = collect(array_keys((array) config('article.inline_ad_positions', [])))
            ->mapWithKeys(fn (string $slot): array => [$slot => $this->sidebar->advertisement($slot)])
            ->all();

        return new ArticlePageData(
            post: $post,
            contentBlocks: $this->contentComposer->compose($post->content, $inlineAdvertisements, (array) config('article.inline_ad_positions', [])),
            breadcrumbs: $breadcrumbs,
            shareUrls: $this->shareUrls($canonicalUrl, $post->title),
            readingTime: max(1, (int) ceil($this->wordCount($post->content) / max(1, (int) config('article.reading_speed_words_per_minute', 220)))),
            previousPost: $this->previousPost($post),
            nextPost: $this->nextPost($post),
            relatedPosts: $this->relatedPosts($post),
            sidebarWidgets: $sidebar['widgets'],
            sidebarSticky: $sidebar['sticky'],
            topAdvertisement: $this->sidebar->advertisement('ARTICLE_TOP'),
            bottomAdvertisement: $this->sidebar->advertisement('ARTICLE_BOTTOM'),
            canonicalUrl: $canonicalUrl,
            seoTitle: $post->effectiveMetaTitle(),
            seoDescription: $seoDescription,
            robots: $this->robots($post),
        );
    }

    /** @return Collection<int, Post> */
    private function relatedPosts(Post $post): Collection
    {
        $limit = max(1, min((int) config('article.related_limit', 6), 12));
        $categoryId = $post->primaryCategory->first()?->getKey();
        $related = new Collection;

        if ($categoryId !== null) {
            $related = $this->cardQuery()
                ->whereKeyNot($post->getKey())
                ->whereHas('categories', fn (Builder $query): Builder => $query->whereKey($categoryId))
                ->latestPublished()->orderByDesc('id')->limit($limit)->get();
        }

        if ($related->count() < $limit) {
            $fill = $this->cardQuery()
                ->whereKeyNot($post->getKey())
                ->whereNotIn('id', $related->modelKeys())
                ->latestPublished()->orderByDesc('id')->limit($limit - $related->count())->get();
            $related = $related->concat($fill);
        }

        return new Collection($related->take($limit)->all());
    }

    private function previousPost(Post $post): ?Post
    {
        return $this->navigationQuery()->where(fn (Builder $query) => $query
            ->where('published_at', '<', $post->published_at)
            ->orWhere(fn (Builder $query) => $query->where('published_at', $post->published_at)->whereKey('<', $post->getKey())))
            ->orderByDesc('published_at')->orderByDesc('id')->first();
    }

    private function nextPost(Post $post): ?Post
    {
        return $this->navigationQuery()->where(fn (Builder $query) => $query
            ->where('published_at', '>', $post->published_at)
            ->orWhere(fn (Builder $query) => $query->where('published_at', $post->published_at)->whereKey('>', $post->getKey())))
            ->orderBy('published_at')->orderBy('id')->first();
    }

    /** @return Builder<Post> */
    private function cardQuery(): Builder
    {
        return Post::query()->select(['id', 'title', 'slug', 'excerpt', 'meta_title', 'featured_image', 'featured_media_id', 'featured_image_alt', 'published_at'])
            ->published()
            ->with(['primaryCategory:id,name,slug', 'featuredMedia:id,disk,path,width,height,missing_at,metadata']);
    }

    /** @return Builder<Post> */
    private function navigationQuery(): Builder
    {
        return Post::query()->select(['id', 'title', 'slug', 'published_at'])->published();
    }

    /** @return array<string, string> */
    private function shareUrls(string $url, string $title): array
    {
        $encodedUrl = rawurlencode($url);
        $encodedTitle = rawurlencode($title);

        return [
            'facebook' => "https://www.facebook.com/sharer/sharer.php?u=$encodedUrl",
            'x' => "https://x.com/intent/post?url=$encodedUrl&text=$encodedTitle",
            'whatsapp' => "https://wa.me/?text=$encodedTitle%20$encodedUrl",
            'telegram' => "https://t.me/share/url?url=$encodedUrl&text=$encodedTitle",
            'email' => "mailto:?subject=$encodedTitle&body=$encodedUrl",
            'canonical' => $url,
        ];
    }

    private function wordCount(string $html): int
    {
        $text = Str::squish(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text) ?: []);
    }

    private function robots(Post $post): string
    {
        return match (PostSeoData::robotsDirective($post->seo_data)) {
            'noindex_follow' => 'noindex, follow',
            'index_nofollow' => 'index, nofollow',
            'noindex_nofollow' => 'noindex, nofollow',
            default => 'index, follow',
        };
    }
}
