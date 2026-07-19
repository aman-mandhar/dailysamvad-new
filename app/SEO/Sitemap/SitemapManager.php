<?php

namespace App\SEO\Sitemap;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\SEO\SocialImageResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use XMLWriter;

class SitemapManager
{
    private const SITEMAP_NS = 'http://www.sitemaps.org/schemas/sitemap/0.9';

    public function __construct(
        private readonly XmlSitemapBuilder $xml,
        private readonly SitemapUrlValidator $urls,
        private readonly SocialImageResolver $images,
        private readonly SitemapCache $cache,
    ) {}

    public function index(): string
    {
        return $this->remember('index', $this->standardTtl(), function (): string {
            $children = [];
            for ($page = 1; $page <= $this->postPageCount(); $page++) {
                $children[] = route('seo.sitemap.posts', $page);
            }
            foreach ([
                'include_categories' => 'seo.sitemap.categories',
                'include_tags' => 'seo.sitemap.tags',
                'include_authors' => 'seo.sitemap.authors',
                'include_pages' => 'seo.sitemap.pages',
                'include_images' => 'seo.sitemap.images',
            ] as $setting => $route) {
                if (config("seo.sitemaps.$setting")) {
                    $children[] = route($route);
                }
            }
            if (config('seo.sitemaps.include_news')) {
                $children[] = route('seo.sitemap.news');
                for ($page = 2; $page <= $this->newsPageCount(); $page++) {
                    $children[] = route('seo.sitemap.news.chunk', $page);
                }
            }

            return $this->xml->document('sitemapindex', ['' => self::SITEMAP_NS], function (XMLWriter $xml) use ($children): void {
                foreach (array_unique($children) as $location) {
                    if ($location = $this->urls->validate($location)) {
                        $this->xml->sitemap($xml, $location);
                    }
                }
            });
        });
    }

    public function enabled(): bool
    {
        return (bool) config('seo.sitemaps.enabled', true);
    }

    public function posts(int $page): ?string
    {
        if (! $this->enabled() || $page < 1 || $page > $this->postPageCount()) {
            return null;
        }

        return $this->remember("posts:$page", $this->standardTtl(), function () use ($page): string {
            $limit = $this->limit();
            $query = $this->postQuery(['id', 'slug', 'canonical_url', 'published_at', 'updated_at'])
                ->orderByDesc('published_at')->orderByDesc('id')->forPage($page, $limit);

            return $this->urlset(function (XMLWriter $xml) use ($query): void {
                foreach ($query->cursor() as $post) {
                    if ($url = $this->postUrl($post)) {
                        $this->xml->url($xml, $url, $this->lastModified($post));
                    }
                }
            });
        });
    }

    public function categories(): ?string
    {
        if (! $this->enabled() || ! config('seo.sitemaps.include_categories')) {
            return null;
        }

        return $this->remember('categories', $this->standardTtl(), fn (): string => $this->urlset(function (XMLWriter $xml): void {
            Category::query()->active()->select(['id', 'slug', 'updated_at'])
                ->whereHas('posts', fn (Builder $query): Builder => $query->published()->indexable())
                ->orderBy('id')->eachById(function (Category $category) use ($xml): void {
                    if ($url = $this->urls->validate(route('categories.show', $category->slug))) {
                        $this->xml->url($xml, $url, $category->updated_at?->timezone(config('app.timezone'))->toIso8601String());
                    }
                });
        }));
    }

    public function tags(): ?string
    {
        if (! $this->enabled() || ! config('seo.sitemaps.include_tags') || str_contains((string) config('archive.robots.tag'), 'noindex')) {
            return null;
        }

        return $this->remember('tags', $this->standardTtl(), fn (): string => $this->urlset(function (XMLWriter $xml): void {
            Tag::query()->select(['id', 'slug', 'updated_at'])
                ->whereHas('posts', fn (Builder $query): Builder => $query->published()->indexable())
                ->orderBy('id')->eachById(function (Tag $tag) use ($xml): void {
                    if ($url = $this->urls->validate(route('tags.show', $tag->slug))) {
                        $this->xml->url($xml, $url, $tag->updated_at?->timezone(config('app.timezone'))->toIso8601String());
                    }
                });
        }));
    }

    public function authors(): ?string
    {
        if (! $this->enabled() || ! config('seo.sitemaps.include_authors') || ! config('archive.author_archives_enabled', true) || str_contains((string) config('archive.robots.author'), 'noindex')) {
            return null;
        }

        return $this->remember('authors', $this->standardTtl(), fn (): string => $this->urlset(function (XMLWriter $xml): void {
            User::query()->select(['id', 'username', 'updated_at', 'is_public', 'is_active'])
                ->where('is_active', true)->publicAuthor()
                ->whereHas('publishedPosts', fn (Builder $query): Builder => $query->indexable())
                ->orderBy('id')->eachById(function (User $author) use ($xml): void {
                    if ($url = $this->urls->validate(route('authors.show', $author->username))) {
                        $this->xml->url($xml, $url, $author->updated_at?->timezone(config('app.timezone'))->toIso8601String());
                    }
                });
        }));
    }

    public function pages(): ?string
    {
        if (! $this->enabled() || ! config('seo.sitemaps.include_pages')) {
            return null;
        }

        return $this->remember('pages', $this->standardTtl(), fn (): string => $this->urlset(function (XMLWriter $xml): void {
            $seen = [];
            foreach ([['route' => 'home', 'robots' => 'index'], ...array_values(config('static-pages'))] as $page) {
                if (str_contains((string) ($page['robots'] ?? 'index'), 'noindex')) {
                    continue;
                }
                $url = $this->urls->validate(route($page['route']));
                if ($url && ! isset($seen[$url])) {
                    $seen[$url] = true;
                    $this->xml->url($xml, $url);
                }
            }
        }));
    }

    public function news(): ?string
    {
        return $this->newsPage(1);
    }

    public function newsPage(int $page): ?string
    {
        if (! $this->enabled() || ! config('seo.sitemaps.include_news')) {
            return null;
        }
        if ($page < 1 || $page > max(1, $this->newsPageCount())) {
            return null;
        }

        return $this->remember("news:$page", (int) config('seo.sitemaps.news_cache_ttl', 300), function () use ($page): string {
            $cutoff = now(config('app.timezone'))->subHours((int) config('seo.news.hours', 48))->utc();
            $limit = min(1000, max(1, (int) config('seo.news.limit', 1000)));
            $query = $this->postQuery(['id', 'title', 'slug', 'canonical_url', 'language', 'published_at'])
                ->where('published_at', '>=', $cutoff)->orderByDesc('published_at')->orderByDesc('id')->forPage($page, $limit);

            return $this->xml->document('urlset', [
                '' => self::SITEMAP_NS,
                'news' => 'http://www.google.com/schemas/sitemap-news/0.9',
            ], function (XMLWriter $xml) use ($query): void {
                foreach ($query->cursor() as $post) {
                    if (! $url = $this->postUrl($post)) {
                        continue;
                    }
                    $xml->startElement('url');
                    $xml->writeElement('loc', $url);
                    $xml->startElement('news:news');
                    $xml->startElement('news:publication');
                    $xml->writeElement('news:name', $this->xml->text((string) config('seo.news.publication_name')) ?? config('organization.website_name'));
                    $xml->writeElement('news:language', $this->newsLanguage($post->language));
                    $xml->endElement();
                    $xml->writeElement('news:publication_date', $post->published_at->timezone(config('app.timezone'))->toIso8601String());
                    $xml->writeElement('news:title', $this->xml->text($post->title) ?? config('organization.website_name'));
                    $xml->endElement();
                    $xml->endElement();
                }
            });
        });
    }

    public function newsPageCount(): int
    {
        if (! $this->enabled() || ! config('seo.sitemaps.include_news')) {
            return 0;
        }
        $cutoff = now(config('app.timezone'))->subHours((int) config('seo.news.hours', 48))->utc();
        $count = $this->postQuery(['id'])->where('published_at', '>=', $cutoff)->count();
        $limit = min(1000, max(1, (int) config('seo.news.limit', 1000)));

        return $count === 0 ? 0 : (int) ceil($count / $limit);
    }

    public function images(): ?string
    {
        if (! $this->enabled() || ! config('seo.sitemaps.include_images')) {
            return null;
        }

        return $this->remember('images', $this->standardTtl(), function (): string {
            $query = $this->postQuery(['id', 'title', 'slug', 'canonical_url', 'featured_image', 'featured_media_id', 'featured_image_alt', 'published_at'])
                ->where(fn (Builder $query): Builder => $query->whereNotNull('featured_image')->orWhereNotNull('featured_media_id'))
                ->with('featuredMedia:id,disk,path,alt_text,caption,width,height,mime_type,missing_at')
                ->orderBy('id')->limit(50000);

            return $this->xml->document('urlset', [
                '' => self::SITEMAP_NS,
                'image' => 'http://www.google.com/schemas/sitemap-image/1.1',
            ], function (XMLWriter $xml) use ($query): void {
                $seen = [];
                foreach ($query->lazyById(500) as $post) {
                    $media = $post->featuredMedia;
                    $image = $this->images->resolve($post->featured_image, $media?->alt_text ?: $post->featured_image_alt ?: $post->title, $media)
                        ?? $this->images->resolve($media?->path, $media?->alt_text ?: $post->title, $media);
                    $pageUrl = $this->postUrl($post);
                    if (! $pageUrl || ! $image || isset($seen[$pageUrl.'|'.$image->url])) {
                        continue;
                    }
                    $seen[$pageUrl.'|'.$image->url] = true;
                    $xml->startElement('url');
                    $xml->writeElement('loc', $pageUrl);
                    $xml->startElement('image:image');
                    $xml->writeElement('image:loc', $image->url);
                    if ($caption = $this->xml->text($media?->caption ?: $media?->alt_text ?: $post->featured_image_alt ?: $post->title)) {
                        $xml->writeElement('image:caption', $caption);
                    }
                    if ($title = $this->xml->text($post->title)) {
                        $xml->writeElement('image:title', $title);
                    }
                    $xml->endElement();
                    $xml->endElement();
                }
            });
        });
    }

    public function robots(): string
    {
        return $this->remember('robots:'.(config('seo.robots.allow_indexing') ? 'allow' : 'block'), (int) config('seo.sitemaps.robots_cache_ttl', 300), function (): string {
            $lines = ['User-agent: *'];
            if (! config('seo.robots.allow_indexing')) {
                $lines[] = 'Disallow: /';
                foreach (array_unique((array) config('seo.robots.disallow', [])) as $path) {
                    $lines[] = 'Disallow: '.rtrim($path, '/');
                }
            } else {
                $lines[] = 'Allow: /';
                foreach (array_unique((array) config('seo.robots.disallow', [])) as $path) {
                    $lines[] = 'Disallow: '.rtrim($path, '/');
                }
            }
            $lines[] = 'Sitemap: '.route('sitemap');
            if (config('seo.sitemaps.include_news')) {
                $lines[] = 'Sitemap: '.route('seo.sitemap.news');
            }

            return implode("\n", array_unique($lines))."\n";
        });
    }

    public function postPageCount(): int
    {
        $count = $this->postQuery(['id'])->count();

        return $count === 0 ? 0 : (int) ceil($count / $this->limit());
    }

    public function invalidate(): void
    {
        $this->cache->invalidate();
    }

    private function postQuery(array $columns): Builder
    {
        return Post::query()->published()->indexable()->select(array_unique($columns));
    }

    private function postUrl(Post $post): ?string
    {
        return $this->urls->validate($post->effectiveCanonicalUrl() ?? $post->publicUrl());
    }

    private function lastModified(Post $post): ?string
    {
        $date = $post->updated_at && $post->published_at && $post->updated_at->gte($post->published_at) ? $post->updated_at : $post->published_at;

        return $date?->timezone(config('app.timezone'))->toIso8601String();
    }

    private function newsLanguage(?string $language): string
    {
        $language = strtolower(strtok(str_replace('_', '-', $language ?: config('seo.news.language', 'en')), '-'));

        return preg_match('/^[a-z]{2,3}$/', $language) ? $language : 'en';
    }

    private function limit(): int
    {
        return min(50000, max(1, (int) config('seo.sitemaps.urls_per_sitemap', 10000)));
    }

    private function standardTtl(): int
    {
        return (int) config('seo.sitemaps.cache_ttl', 3600);
    }

    private function urlset(callable $write): string
    {
        return $this->xml->document('urlset', ['' => self::SITEMAP_NS], $write);
    }

    private function remember(string $name, int $ttl, callable $callback): string
    {
        if ($ttl <= 0) {
            return $callback();
        }

        return Cache::remember($this->cache->key($name), now()->addSeconds($ttl), $callback);
    }
}
