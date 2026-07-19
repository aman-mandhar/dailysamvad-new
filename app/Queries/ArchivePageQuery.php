<?php

namespace App\Queries;

use App\Data\ArchivePageData;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ArchivePageQuery
{
    public function __construct(
        private readonly SidebarQuery $sidebar,
    ) {}

    public function forCategory(string $slug): ArchivePageData
    {
        $category = Category::query()->active()->where('slug', $slug)->firstOrFail();

        return $this->build(
            type: 'category',
            title: $category->name,
            description: $this->plainText($category->description),
            entity: $category,
            query: $this->postQuery()->whereHas('categories', fn (Builder $query): Builder => $query->whereKey($category->getKey())),
            baseUrl: route('categories.show', $category->slug),
            metaTitle: $category->meta_title ?: $category->name.' News',
            metaDescription: $category->meta_description,
        );
    }

    public function forTag(string $slug): ArchivePageData
    {
        $tag = Tag::query()->where('slug', $slug)->firstOrFail();

        return $this->build(
            type: 'tag',
            title: $tag->name,
            description: $this->plainText($tag->description),
            entity: $tag,
            query: $this->postQuery()->whereHas('tags', fn (Builder $query): Builder => $query->whereKey($tag->getKey())),
            baseUrl: route('tags.show', $tag->slug),
            metaTitle: $tag->meta_title ?: $tag->name.' News',
            metaDescription: $tag->meta_description,
        );
    }

    public function forAuthor(string $username): ArchivePageData
    {
        abort_unless((bool) config('archive.author_archives_enabled', true), 404);
        $author = User::query()
            ->select(['id', 'name', 'username', 'slug', 'bio', 'designation', 'avatar_path', 'facebook_url', 'x_url', 'instagram_url', 'youtube_url', 'is_public'])
            ->publicAuthor()
            ->where('username', $username)
            ->firstOrFail();

        return $this->build(
            type: 'author',
            title: $author->name,
            description: $this->plainText($author->bio),
            entity: $author,
            query: $this->postQuery()->where('author_id', $author->getKey()),
            baseUrl: route('authors.show', $author->username),
            metaTitle: $author->name.' - Author at '.config('organization.website_name'),
            metaDescription: $author->bio,
        );
    }

    public function forSearch(string $term): ArchivePageData
    {
        $term = $this->normalizeSearch($term);
        $query = $this->postQuery();

        if ($term === '' || mb_strlen($term) < (int) config('archive.search_min_length', 1)) {
            $query->whereRaw('1 = 0');
        } else {
            $like = '%'.$this->escapeLike($term).'%';
            $query->where(fn (Builder $query): Builder => $query
                ->whereRaw("title LIKE ? ESCAPE '\\'", [$like])
                ->orWhereRaw("excerpt LIKE ? ESCAPE '\\'", [$like])
                ->orWhereRaw("content LIKE ? ESCAPE '\\'", [$like])
                ->orWhereRaw("meta_title LIKE ? ESCAPE '\\'", [$like])
                ->orWhereRaw("meta_description LIKE ? ESCAPE '\\'", [$like]));
        }

        $baseUrl = route('search', $term === '' ? [] : ['q' => $term]);

        return $this->build(
            type: 'search',
            title: $term === '' ? 'Search News' : 'Search results for “'.$term.'”',
            description: $term === '' ? 'Enter a keyword to search published news.' : 'Published news matching “'.$term.'”.',
            entity: null,
            query: $query,
            baseUrl: $baseUrl,
            metaTitle: $term === '' ? 'Search '.config('organization.website_name') : 'Search results for '.$term,
            metaDescription: $term === '' ? 'Search published news on '.config('organization.website_name').'.' : 'Search results for '.$term.' on '.config('organization.website_name').'.',
            searchQuery: $term,
        );
    }

    public function forDate(int $year, ?int $month = null, ?int $day = null): ArchivePageData
    {
        abort_if($year < 1900 || $year > 2100 || ($day !== null && $month === null), 404);
        abort_if($month !== null && ! checkdate($month, $day ?? 1, $year), 404);

        $timezone = config('app.timezone');
        $start = CarbonImmutable::create($year, $month ?? 1, $day ?? 1, 0, 0, 0, $timezone);
        $end = $day !== null ? $start->endOfDay() : ($month !== null ? $start->endOfMonth() : $start->endOfYear());
        $title = $day !== null
            ? $start->translatedFormat('d F Y').' News'
            : ($month !== null ? $start->translatedFormat('F Y').' News' : $year.' News');
        $route = $day !== null ? 'archives.day' : ($month !== null ? 'archives.month' : 'archives.year');
        $parameters = array_filter(['year' => $year, 'month' => $month, 'day' => $day], fn (mixed $value): bool => $value !== null);

        return $this->build(
            type: 'date',
            title: $title,
            description: 'Published news from '.$title.'.',
            entity: $start,
            query: $this->postQuery()->whereBetween('published_at', [$start->utc(), $end->utc()]),
            baseUrl: route($route, $parameters),
            metaTitle: $title,
            metaDescription: 'Browse '.$title.' from '.config('organization.website_name').'.',
            dateParts: $parameters,
        );
    }

    /** @param array<string, int> $dateParts */
    private function build(
        string $type,
        string $title,
        ?string $description,
        mixed $entity,
        Builder $query,
        string $baseUrl,
        string $metaTitle,
        ?string $metaDescription,
        ?string $searchQuery = null,
        array $dateParts = [],
    ): ArchivePageData {
        $posts = $query->paginate((int) config('archive.per_page', 12));
        if ($searchQuery !== null && $searchQuery !== '') {
            $posts->appends(['q' => $searchQuery]);
        }

        $page = max(1, $posts->currentPage());
        $canonical = $page > 1 ? $baseUrl.(str_contains($baseUrl, '?') ? '&' : '?').'page='.$page : $baseUrl;
        $pageSuffix = $page > 1 ? ' - Page '.$page : '';
        $description = $description ?: 'Browse published news from '.config('organization.website_name').'.';
        $seoDescription = $this->plainText($metaDescription) ?: $description;
        $breadcrumbs = $this->breadcrumbs($type, $title, $baseUrl, $dateParts);
        $sidebarContext = (string) config("archive.sidebar_contexts.$type", 'archive');
        $sidebar = $this->sidebar->forContext($sidebarContext);
        $ads = (array) config("archive.advertisements.$type", []);
        $authorAvatarUrl = null;
        $authorSocialLinks = [];
        if ($entity instanceof User) {
            $authorAvatarUrl = $entity->avatar_url;
            foreach (['facebook_url' => 'Facebook', 'x_url' => 'X', 'instagram_url' => 'Instagram', 'youtube_url' => 'YouTube'] as $field => $label) {
                if (filter_var($entity->{$field}, FILTER_VALIDATE_URL)) {
                    $authorSocialLinks[] = ['label' => $label, 'url' => $entity->{$field}];
                }
            }
        }

        return new ArchivePageData(
            contextType: $type,
            label: (string) config("archive.labels.$type", ucfirst($type)),
            title: $title,
            description: $description,
            entity: $entity,
            posts: $posts,
            breadcrumbs: $breadcrumbs,
            sidebarWidgets: $sidebar['widgets'],
            sidebarSticky: $sidebar['sticky'],
            sidebarContext: $sidebarContext,
            topAdvertisement: $this->sidebar->advertisement((string) ($ads['top'] ?? 'ARCHIVE_TOP')),
            inlineAdvertisement: $this->sidebar->advertisement((string) ($ads['inline'] ?? 'ARCHIVE_INLINE')),
            seoTitle: $metaTitle.$pageSuffix,
            seoDescription: $seoDescription.($page > 1 ? ' Page '.$page.'.' : ''),
            canonicalUrl: $canonical,
            robots: (string) config("archive.robots.$type", 'index, follow'),
            searchQuery: $searchQuery,
            emptyState: $searchQuery === '' ? 'Enter a keyword to search published news.' : (string) config("archive.empty_states.$type"),
            authorAvatarUrl: $authorAvatarUrl,
            authorSocialLinks: $authorSocialLinks,
        );
    }

    /** @return Builder<Post> */
    private function postQuery(): Builder
    {
        return Post::query()
            ->select(['id', 'title', 'slug', 'excerpt', 'meta_title', 'featured_image', 'featured_media_id', 'featured_image_alt', 'published_at'])
            ->published()->with(['primaryCategory:id,name,slug', 'featuredMedia:id,disk,path,width,height,missing_at,metadata'])
            ->orderByDesc('published_at')->orderByDesc('id');
    }

    /** @param array<string, int> $dateParts */
    private function breadcrumbs(string $type, string $title, string $url, array $dateParts): Collection
    {
        $items = [['label' => 'Home', 'url' => route('home'), 'current' => false]];
        if ($type === 'date' && isset($dateParts['year'])) {
            $items[] = ['label' => (string) $dateParts['year'], 'url' => route('archives.year', $dateParts['year']), 'current' => count($dateParts) === 1];
            if (isset($dateParts['month'])) {
                $items[] = ['label' => CarbonImmutable::create($dateParts['year'], $dateParts['month'])->translatedFormat('F'), 'url' => route('archives.month', [$dateParts['year'], $dateParts['month']]), 'current' => count($dateParts) === 2];
            }
            if (isset($dateParts['day'])) {
                $items[] = ['label' => (string) $dateParts['day'], 'url' => $url, 'current' => true];
            }
        } else {
            $items[] = ['label' => $title, 'url' => $url, 'current' => true];
        }

        return collect($items);
    }

    private function normalizeSearch(string $term): string
    {
        return Str::limit(Str::squish(strip_tags($term)), (int) config('archive.search_max_length', 200), '');
    }

    private function escapeLike(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }

    private function plainText(?string $value): ?string
    {
        $withoutExecutableBlocks = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/isu', '', (string) $value) ?? '';
        $text = Str::squish(html_entity_decode(strip_tags($withoutExecutableBlocks), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return $text === '' ? null : $text;
    }
}
