<?php

namespace App\SEO;

use App\Data\ArchivePageData;
use App\Data\ArticlePageData;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class StructuredDataBuilder
{
    public function __construct(private readonly SocialImageResolver $images) {}

    public function article(ArticlePageData $article, OpenGraphData $social, string $description): SchemaGraph
    {
        $post = $article->post;
        $canonical = $social->url;
        $language = $this->language($post->language);
        $graph = $this->common($language);
        $breadcrumbId = $this->id($canonical, 'breadcrumb');
        $webPageId = $this->id($canonical, 'webpage');
        $articleId = $this->id($canonical, 'article');
        $imageId = $social->image ? $this->id($canonical, 'primaryimage') : null;
        $author = $this->person($post->author);
        $published = $this->date($post->published_at);
        $modified = $this->modifiedDate($post->published_at, $post->updated_at);
        $section = $this->text($post->primaryCategory->first()?->name ?? $post->categories->first()?->name);
        $keywords = $this->textList([$section, ...$post->tags->pluck('name')->all()]);

        $graph[] = $this->compact([
            '@type' => 'WebPage',
            '@id' => $webPageId,
            'url' => $canonical,
            'name' => $this->text($social->title),
            'description' => $this->text($description),
            'isPartOf' => $this->reference($this->websiteId()),
            'breadcrumb' => $this->reference($breadcrumbId),
            'primaryImageOfPage' => $imageId ? $this->reference($imageId) : null,
            'inLanguage' => $language,
        ]);

        if ($social->image && $imageId) {
            $graph[] = $this->compact([
                '@type' => 'ImageObject',
                '@id' => $imageId,
                'url' => $social->image->url,
                'contentUrl' => $social->image->url,
                'caption' => $this->text($social->image->alt),
                'width' => $social->image->width,
                'height' => $social->image->height,
            ]);
        }

        if ($author) {
            $graph[] = $author;
        }

        $graph[] = $this->compact([
            '@type' => 'NewsArticle',
            '@id' => $articleId,
            'url' => $canonical,
            'mainEntityOfPage' => $this->reference($webPageId),
            'headline' => $this->text($post->title) ?? $this->text($social->title),
            'description' => $this->text($description),
            'datePublished' => $published,
            'dateModified' => $modified,
            'author' => $author ? $this->reference($author['@id']) : $this->reference($this->organizationId()),
            'publisher' => $this->reference($this->organizationId()),
            'image' => $imageId ? $this->reference($imageId) : null,
            'articleSection' => $section,
            'keywords' => $keywords,
            'articleBody' => $this->articleBody($post->content),
            'inLanguage' => $language,
            'isPartOf' => $this->reference($this->websiteId()),
        ]);

        if ($breadcrumb = $this->breadcrumb($article->breadcrumbs, $canonical, $breadcrumbId)) {
            $graph[] = $breadcrumb;
        }

        return $this->graph($graph);
    }

    public function archive(ArchivePageData $archive, OpenGraphData $social, string $description): SchemaGraph
    {
        $language = $this->language();
        $graph = $this->common($language);
        $canonical = $social->url;
        $webPageId = $this->id($canonical, 'webpage');
        $breadcrumbId = $this->id($canonical, 'breadcrumb');
        $person = $archive->entity instanceof User ? $this->person($archive->entity) : null;
        $type = match ($archive->contextType) {
            'author' => $person ? 'ProfilePage' : 'CollectionPage',
            'search' => 'SearchResultsPage',
            default => 'CollectionPage',
        };

        if ($person) {
            $graph[] = $person;
        }

        $graph[] = $this->compact([
            '@type' => $type,
            '@id' => $webPageId,
            'url' => $canonical,
            'name' => $this->text($social->title),
            'description' => $this->text($description),
            'isPartOf' => $this->reference($this->websiteId()),
            'breadcrumb' => $this->reference($breadcrumbId),
            'mainEntity' => $person ? $this->reference($person['@id']) : null,
            'inLanguage' => $language,
        ]);

        if ($breadcrumb = $this->breadcrumb($archive->breadcrumbs, $canonical, $breadcrumbId)) {
            $graph[] = $breadcrumb;
        }

        return $this->graph($graph);
    }

    /** @param array<string, mixed> $page */
    public function staticPage(array $page, OpenGraphData $social, string $description): SchemaGraph
    {
        $language = $this->language();
        $canonical = $social->url;
        $breadcrumbId = $this->id($canonical, 'breadcrumb');
        $graph = $this->common($language);
        $graph[] = $this->compact([
            '@type' => match ($page['route'] ?? null) {
                'pages.about' => 'AboutPage',
                'pages.contact' => 'ContactPage',
                default => 'WebPage',
            },
            '@id' => $this->id($canonical, 'webpage'),
            'url' => $canonical,
            'name' => $this->text($social->title),
            'description' => $this->text($description),
            'isPartOf' => $this->reference($this->websiteId()),
            'breadcrumb' => $this->reference($breadcrumbId),
            'inLanguage' => $language,
        ]);
        $breadcrumbs = collect([
            ['label' => 'Home', 'url' => route('home'), 'current' => false],
            ['label' => $page['title'] ?? $social->title, 'url' => $canonical, 'current' => true],
        ]);
        $graph[] = $this->breadcrumb($breadcrumbs, $canonical, $breadcrumbId);

        return $this->graph($graph);
    }

    public function home(OpenGraphData $social, string $description): SchemaGraph
    {
        $language = $this->language();
        $graph = $this->common($language);
        $graph[] = $this->compact([
            '@type' => 'WebPage',
            '@id' => $this->id($social->url, 'webpage'),
            'url' => $social->url,
            'name' => $this->text($social->title),
            'description' => $this->text($description),
            'isPartOf' => $this->reference($this->websiteId()),
            'inLanguage' => $language,
        ]);

        return $this->graph($graph);
    }

    public function generic(OpenGraphData $social, string $description): SchemaGraph
    {
        return $this->home($social, $description);
    }

    /** @return array<int, array<string, mixed>> */
    private function common(string $language): array
    {
        $organization = $this->organization();
        $website = $this->compact([
            '@type' => 'WebSite',
            '@id' => $this->websiteId(),
            'url' => route('home'),
            'name' => $this->text(config('organization.website_name')),
            'description' => $this->text(config('seo.site_description')),
            'publisher' => $this->reference($this->organizationId()),
            'inLanguage' => $language,
            'potentialAction' => $this->searchAction(),
        ]);

        return [$organization, $website];
    }

    /** @return array<string, mixed> */
    private function organization(): array
    {
        $logoSource = config('seo.publisher_logo') ?: config('seo.site_logo');
        $logo = $logoSource === config('seo.default_social_image')
            ? $this->images->configuredDefault(config('organization.website_name'))
            : $this->images->resolve($logoSource, config('organization.website_name'));
        $sameAs = $this->urls([
            ...(array) config('organization.social_links', []),
            config('seo.publisher_url'),
        ]);
        $logoNode = $logo ? $this->compact([
            '@type' => 'ImageObject',
            '@id' => $this->id(route('home'), 'logo'),
            'url' => $logo->url,
            'contentUrl' => $logo->url,
            'caption' => $this->text(config('organization.website_name')),
            'width' => $logo->width,
            'height' => $logo->height,
        ]) : null;

        return $this->compact([
            '@type' => in_array(config('seo.publisher_type'), ['NewsMediaOrganization', 'Organization'], true)
                ? config('seo.publisher_type') : 'NewsMediaOrganization',
            '@id' => $this->organizationId(),
            'name' => $this->text(config('organization.organization_name')) ?? $this->text(config('organization.website_name')),
            'alternateName' => $this->text(config('seo.publisher_alternate_name')),
            'url' => route('home'),
            'description' => $this->text(config('seo.publisher_description')),
            'logo' => $logoNode,
            'sameAs' => $sameAs,
        ]);
    }

    /** @return array<string, mixed>|null */
    private function searchAction(): ?array
    {
        if (! config('seo.schema_search_action', true) || ! Route::has('search')) {
            return null;
        }

        return [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => route('search').'?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ];
    }

    /** @return array<string, mixed>|null */
    private function person(?User $author): ?array
    {
        if (! $author?->is_public || blank($author->name) || blank($author->username)) {
            return null;
        }
        $url = route('authors.show', $author->username);
        $image = $this->images->resolve($author->avatar_url, $author->name);
        $sameAs = $this->urls([
            $author->getAttribute('facebook_url'),
            $author->getAttribute('x_url'),
            $author->getAttribute('instagram_url'),
            $author->getAttribute('youtube_url'),
        ]);

        return $this->compact([
            '@type' => 'Person',
            '@id' => $this->id($url, 'person'),
            'name' => $this->text($author->name),
            'url' => $url,
            'image' => $image ? ['@type' => 'ImageObject', 'url' => $image->url, 'contentUrl' => $image->url] : null,
            'description' => $this->text($author->getAttribute('bio')),
            'jobTitle' => $this->text($author->getAttribute('designation')),
            'sameAs' => $sameAs,
            'worksFor' => $this->reference($this->organizationId()),
        ]);
    }

    /** @param Collection<int, array{label: string, url: ?string, current: bool}> $items
     * @return array<string, mixed>|null
     */
    private function breadcrumb(Collection $items, string $canonical, string $id): ?array
    {
        $seen = [];
        $elements = [];
        foreach ($items->values() as $item) {
            $name = $this->text($item['label'] ?? null);
            $url = ($item['current'] ?? false) ? $canonical : $this->url($item['url'] ?? null);
            if (! $name || ! $url || isset($seen[Str::lower($url)])) {
                continue;
            }
            $seen[Str::lower($url)] = true;
            $elements[] = ['@type' => 'ListItem', 'position' => count($elements) + 1, 'name' => $name, 'item' => $url];
        }

        return count($elements) > 1 ? ['@type' => 'BreadcrumbList', '@id' => $id, 'itemListElement' => $elements] : null;
    }

    /** @param array<int, array<string, mixed>|null> $nodes */
    private function graph(array $nodes): SchemaGraph
    {
        $unique = collect($nodes)->filter()->map(fn (array $node): array => $this->compact($node))
            ->unique(fn (array $node): string => (string) ($node['@id'] ?? serialize($node)))->values()->all();

        return new SchemaGraph($unique);
    }

    /** @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function compact(array $values): array
    {
        return collect($values)->reject(fn (mixed $value): bool => $value === null || $value === '' || $value === [])->all();
    }

    /** @return array{0: string}|array<string, string> */
    private function reference(string $id): array
    {
        return ['@id' => $id];
    }

    private function websiteId(): string
    {
        return $this->id(route('home'), 'website');
    }

    private function organizationId(): string
    {
        return $this->id(route('home'), 'organization');
    }

    private function id(string $url, string $fragment): string
    {
        $url = preg_replace('/#.*$/', '', $url);

        return str_contains($url, '?') ? $url.'#'.$fragment : rtrim($url, '/').'/#'.$fragment;
    }

    private function language(?string $locale = null): string
    {
        $locale = str_replace('_', '-', trim($locale ?: app()->getLocale()));
        $language = Str::lower(Str::before($locale, '-'));
        $country = str_contains($locale, '-') ? Str::upper(Str::after($locale, '-')) : '';
        if (! preg_match('/^[a-z]{2}$/', $language)) {
            return 'en-IN';
        }
        if (! preg_match('/^[A-Z]{2}$/', $country)) {
            $country = Str::upper((string) config('seo.locale_country', 'IN'));
        }

        return $language.'-'.$country;
    }

    private function date(mixed $date): ?string
    {
        return $date instanceof CarbonInterface ? $date->copy()->timezone(config('app.timezone'))->toIso8601String() : null;
    }

    private function modifiedDate(mixed $published, mixed $modified): ?string
    {
        if (! $published instanceof CarbonInterface) {
            return null;
        }
        if (! $modified instanceof CarbonInterface || $modified->lt($published)) {
            $modified = $published;
        }

        return $this->date($modified);
    }

    private function articleBody(?string $content): ?string
    {
        $text = $this->text(preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>|\[[^\]]+\]/isu', ' ', (string) $content));

        return $text ? Str::limit($text, max(500, (int) config('seo.article_body_limit', 5000)), '') : null;
    }

    private function text(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $withoutExecutable = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/isu', ' ', $value) ?? '';
        $text = Str::squish(html_entity_decode(strip_tags($withoutExecutable), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

        return $text !== '' ? $text : null;
    }

    /** @param array<int, mixed> $values
     * @return array<int, string>
     */
    private function textList(array $values): array
    {
        return collect($values)->map(fn (mixed $value): ?string => $this->text($value))->filter()
            ->unique(fn (string $value): string => Str::lower($value))->values()->all();
    }

    /** @param array<int|string, mixed> $values
     * @return array<int, string>
     */
    private function urls(array $values): array
    {
        return collect($values)->flatten()->map(fn (mixed $value): ?string => $this->url($value))->filter()
            ->unique(fn (string $value): string => Str::lower($value))->values()->all();
    }

    private function url(mixed $value): ?string
    {
        if (! is_string($value) || str_contains($value, '\\') || preg_match('/[\x00-\x1F\x7F]/', $value) || preg_match('/%(?![0-9A-Fa-f]{2})/', $value)) {
            return null;
        }
        $value = str_replace(' ', '%20', trim($value));
        if (app()->environment('production') && Str::startsWith($value, 'http://')) {
            $value = 'https://'.Str::after($value, 'http://');
        }

        return filter_var($value, FILTER_VALIDATE_URL) && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true) ? $value : null;
    }
}
