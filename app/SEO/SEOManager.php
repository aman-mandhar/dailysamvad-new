<?php

namespace App\SEO;

use App\Data\ArchivePageData;
use App\Data\ArticlePageData;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;

class SEOManager
{
    public function __construct(
        private readonly SocialImageResolver $images,
        private readonly StructuredDataBuilder $structuredData,
    ) {}

    /** @param array<string, mixed> $context */
    public function forCurrentPage(array $context = []): SEOData
    {
        if (($context['article'] ?? null) instanceof ArticlePageData) {
            return $this->article($context['article']);
        }
        if (($context['archive'] ?? null) instanceof ArchivePageData) {
            return $this->archive($context['archive']);
        }
        if (is_array($context['page'] ?? null)) {
            return $this->staticPage($context['page']);
        }
        if (filled($context['title'] ?? null) || filled($context['robots'] ?? null)) {
            return $this->generic($context);
        }

        return $this->home($context['heroPost'] ?? null);
    }

    public function article(ArticlePageData $article): SEOData
    {
        $post = $article->post;
        $description = $this->description($post->meta_description, $post->excerpt, $post->content);
        $customKeywords = array_filter([(string) $post->focus_keyword, ...(array) data_get($post->seo_data, 'keywords', [])]);
        $taxonomy = [
            ...$post->categories->pluck('name')->all(),
            ...$post->tags->pluck('name')->all(),
        ];

        $title = $this->title($post->meta_title, $post->title);
        $socialTitle = $this->clean(data_get($post->seo_data, 'open_graph.title')) ?? $this->clean($title) ?? $this->siteName();
        $socialDescription = $this->description(data_get($post->seo_data, 'open_graph.description'), $description);
        $media = $post->relationLoaded('featuredMedia') ? $post->featuredMedia : null;
        $imageAlt = $media?->alt_text ?: $post->featured_image_alt ?: $post->title;
        $image = $this->images->resolve($media?->path, $imageAlt, $media)
            ?? $this->images->resolve($post->featured_image, $imageAlt)
            ?? $this->images->configuredDefault($post->title);
        $canonical = $this->publicUrl($post->effectiveCanonicalUrl() ?? $article->canonicalUrl);
        $authorUrl = $post->author?->is_public && filled($post->author?->username)
            ? route('authors.show', $post->author->username)
            : null;
        $section = $this->clean($post->primaryCategory->first()?->name ?? $post->categories->first()?->name);
        $articleTags = $this->cleanList($post->tags->pluck('name')->all());
        $openGraph = new OpenGraphData(
            title: $socialTitle,
            description: $socialDescription,
            url: $canonical,
            type: 'article',
            siteName: $this->siteName(),
            locale: $this->locale($post->language),
            image: $image,
            publishedTime: $this->date($post->published_at),
            modifiedTime: $this->date($post->updated_at),
            authorUrl: $authorUrl,
            publisherUrl: $this->validUrl(config('seo.publisher_url')),
            section: $section,
            tags: $articleTags,
        );

        return new SEOData(
            title: $title,
            description: $description,
            keywords: $this->keywords([...$customKeywords, ...$taxonomy]),
            author: filled($post->author?->name) ? $post->author->name : $this->siteName(),
            robots: $this->robots($article->robots, $post->seo_data),
            canonical: $canonical,
            openGraph: $openGraph,
            twitter: $this->twitter($openGraph, $this->twitterHandle(config('seo.twitter_site')), $this->twitterHandle($post->author?->x_url)),
            schema: $this->structuredData->article($article, $openGraph, $description),
        );
    }

    public function archive(ArchivePageData $archive): SEOData
    {
        $entityKeyword = match (true) {
            $archive->entity instanceof Category, $archive->entity instanceof Tag => $archive->entity->name,
            $archive->entity instanceof User => $archive->entity->name,
            default => $archive->searchQuery,
        };

        $title = $this->title($archive->seoTitle);
        $description = $this->description($archive->seoDescription, $archive->description);
        $contextImage = match (true) {
            $archive->entity instanceof Category => $archive->entity->image_url,
            $archive->entity instanceof User => $archive->authorAvatarUrl,
            default => null,
        };
        $image = $this->images->resolve($contextImage, $archive->title) ?? $this->images->configuredDefault($archive->title);
        $openGraph = $this->websiteGraph($title, $description, $archive->canonicalUrl, $image);

        return new SEOData(
            title: $title,
            description: $description,
            keywords: $this->keywords([$entityKeyword, $archive->label, 'news']),
            author: $archive->entity instanceof User ? $archive->entity->name : $this->siteName(),
            robots: $this->robots($archive->robots),
            canonical: $archive->canonicalUrl,
            openGraph: $openGraph,
            twitter: $this->twitter($openGraph, $this->twitterHandle(config('seo.twitter_site')), $archive->entity instanceof User ? $this->twitterHandle($archive->entity->x_url) : null),
            schema: $this->structuredData->archive($archive, $openGraph, $description),
        );
    }

    /** @param array<string, mixed> $page */
    public function staticPage(array $page): SEOData
    {
        $title = $this->title($page['seo_title'] ?? $page['title'] ?? null);
        $description = $this->description($page['seo_description'] ?? null, $page['description'] ?? null);
        $canonical = route((string) $page['route']);
        $image = $this->images->resolve($page['image'] ?? null, $title) ?? $this->images->configuredDefault($title);
        $openGraph = $this->websiteGraph($title, $description, $canonical, $image);

        return new SEOData(
            title: $title,
            description: $description,
            keywords: $this->keywords([$page['title'] ?? null]),
            author: $this->siteName(),
            robots: $this->robots($page['robots'] ?? null),
            canonical: $canonical,
            openGraph: $openGraph,
            twitter: $this->twitter($openGraph, $this->twitterHandle(config('seo.twitter_site'))),
            schema: $this->structuredData->staticPage($page, $openGraph, $description),
        );
    }

    public function home(?Post $heroPost = null): SEOData
    {
        $canonical = route('home');
        $page = max(1, (int) request()->query('page', 1));
        if ($page > 1) {
            $canonical .= '?page='.$page;
        }

        $title = 'Rzana Punjab - Latest Hindi, Punjabi and English News';
        $description = 'Read the latest breaking, featured and regional news from Rzana Punjab in Hindi, Punjabi and English.';
        $image = $this->images->configuredDefault($this->siteName());
        $openGraph = $this->websiteGraph($title, $description, $canonical, $image);

        return new SEOData(
            title: $title,
            description: $description,
            keywords: $this->keywords(['Rzana Punjab', 'latest news', 'Hindi news', 'Punjabi news', 'English news']),
            author: $this->siteName(),
            robots: 'index, follow',
            canonical: $canonical,
            openGraph: $openGraph,
            twitter: $this->twitter($openGraph, $this->twitterHandle(config('seo.twitter_site'))),
            schema: $this->structuredData->home($openGraph, $description),
        );
    }

    /** @param array<string, mixed> $context */
    private function generic(array $context): SEOData
    {
        $title = $this->title($context['title'] ?? null);
        $description = $this->description($context['description'] ?? null);
        $canonical = $context['canonical'] ?? url()->current();
        $openGraph = $this->websiteGraph($title, $description, $canonical, $this->images->configuredDefault($title));

        return new SEOData(
            title: $title,
            description: $description,
            keywords: [],
            author: $this->siteName(),
            robots: $this->robots($context['robots'] ?? null),
            canonical: $canonical,
            openGraph: $openGraph,
            twitter: $this->twitter($openGraph, $this->twitterHandle(config('seo.twitter_site'))),
            schema: $this->structuredData->generic($openGraph, $description),
        );
    }

    private function websiteGraph(string $title, string $description, string $url, ?SocialImage $image): OpenGraphData
    {
        return new OpenGraphData($title, $description, $url, 'website', $this->siteName(), $this->locale(), $image);
    }

    private function twitter(OpenGraphData $graph, ?string $site = null, ?string $creator = null): TwitterCardData
    {
        return new TwitterCardData($graph->image?->large ? 'summary_large_image' : 'summary', $graph->title, $graph->description, $graph->image, $site, $creator);
    }

    private function locale(?string $locale = null): string
    {
        $locale = str_replace('-', '_', trim($locale ?: app()->getLocale()));
        if (preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale)) {
            return $locale;
        }
        $language = Str::lower(Str::before($locale, '_'));
        $country = Str::upper((string) config('seo.locale_country', 'IN'));

        return preg_match('/^[a-z]{2}$/', $language) && preg_match('/^[A-Z]{2}$/', $country) ? "{$language}_{$country}" : 'en_IN';
    }

    private function twitterHandle(mixed $value): ?string
    {
        if (! is_string($value) || blank($value)) {
            return null;
        }
        $value = trim($value);
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            $host = Str::lower((string) parse_url($value, PHP_URL_HOST));
            if (! in_array($host, ['x.com', 'www.x.com', 'twitter.com', 'www.twitter.com'], true)) {
                return null;
            }
            $value = trim((string) parse_url($value, PHP_URL_PATH), '/');
        }
        $value = ltrim(preg_replace('/\s+/', '', $value) ?? '', '@');

        return preg_match('/^[A-Za-z0-9_]{1,15}$/', $value) ? '@'.$value : null;
    }

    private function date(mixed $value): ?string
    {
        return $value?->timezone(config('app.timezone'))->toIso8601String();
    }

    private function validUrl(mixed $value): ?string
    {
        return is_string($value) && filter_var($value, FILTER_VALIDATE_URL) && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true) ? $value : null;
    }

    private function publicUrl(string $url): string
    {
        if (app()->environment('production') && Str::startsWith($url, 'http://')) {
            return 'https://'.Str::after($url, 'http://');
        }

        return $url;
    }

    private function clean(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $clean = Str::squish(html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $clean !== '' ? $clean : null;
    }

    /** @param array<int, mixed> $values
     * @return array<int, string>
     */
    private function cleanList(array $values): array
    {
        return collect($values)->map(fn (mixed $value): ?string => $this->clean($value))->filter()
            ->unique(fn (string $value): string => Str::lower($value))->values()->all();
    }

    private function title(?string ...$candidates): string
    {
        return collect($candidates)->first(fn (?string $value): bool => filled($value)) ?: $this->siteName();
    }

    private function description(?string ...$candidates): string
    {
        $value = collect($candidates)->first(fn (?string $candidate): bool => filled($candidate)) ?: $this->siteName();
        $withoutExecutable = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/isu', ' ', $value) ?? '';
        $plain = Str::squish(html_entity_decode(strip_tags($withoutExecutable), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return Str::limit($plain, 160, '');
    }

    /** @param array<int, mixed> $values
     * @return array<int, string>
     */
    private function keywords(array $values): array
    {
        return collect($values)->flatten()->filter(fn (mixed $value): bool => is_string($value) && filled($value))
            ->flatMap(fn (string $value) => preg_split('/\s*,\s*/u', $value) ?: [])
            ->map(fn (string $value): string => Str::squish(strip_tags($value)))
            ->filter()->unique(fn (string $value): string => Str::lower($value))->values()->all();
    }

    /** @param array<string, mixed>|null $seoData */
    private function robots(?string $value, ?array $seoData = null): string
    {
        $requested = array_filter([
            ...preg_split('/\s*,\s*/', Str::lower((string) $value)) ?: [],
            data_get($seoData, 'noarchive') ? 'noarchive' : null,
            data_get($seoData, 'nosnippet') ? 'nosnippet' : null,
        ]);
        $allowed = ['index', 'noindex', 'follow', 'nofollow', 'noarchive', 'nosnippet'];
        $directives = collect($requested)->filter(fn (string $directive): bool => in_array($directive, $allowed, true))->unique()->values();

        if (! $directives->contains(fn (string $directive): bool => in_array($directive, ['index', 'noindex'], true))) {
            $directives->prepend('index');
        }
        if (! $directives->contains(fn (string $directive): bool => in_array($directive, ['follow', 'nofollow'], true))) {
            $directives->push('follow');
        }

        return $directives->implode(', ');
    }

    private function siteName(): string
    {
        return (string) config('organization.website_name', 'Rzana Punjab');
    }
}
