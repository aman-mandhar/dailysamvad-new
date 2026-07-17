<?php

namespace App\Queries;

use App\Data\AdvertisementData;
use App\Data\SidebarWidgetData;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class SidebarQuery
{
    /** @return array{sticky: bool, widgets: Collection<int, SidebarWidgetData>} */
    public function forHomepage(): array
    {
        return $this->forContext('homepage');
    }

    public function advertisement(string $slot): AdvertisementData
    {
        return AdvertisementData::fromConfig(
            $slot,
            (array) config("advertisements.slots.$slot", []),
            (bool) config('advertisements.show_placeholders', false),
        );
    }

    /** @return array{sticky: bool, widgets: Collection<int, SidebarWidgetData>} */
    public function forContext(string $context): array
    {
        $configuration = config("sidebar.$context", []);
        $definitions = collect($configuration['widgets'] ?? [])->filter(fn (array $widget): bool => (bool) ($widget['enabled'] ?? false));

        if ($definitions->isEmpty()) {
            return ['sticky' => false, 'widgets' => collect()];
        }

        $types = $definitions->pluck('type');
        $hasPosts = $types->intersect(['latest-news', 'popular-news', 'categories'])->isNotEmpty() && Schema::hasTable('posts');
        $hasCategories = $types->contains('categories') && Schema::hasTable('categories');
        $hasViewCounts = $types->contains('popular-news') && $hasPosts && $this->supportsViewCounts();

        $widgets = $definitions->map(fn (array $definition): ?SidebarWidgetData => match ($definition['type'] ?? null) {
            'latest-news' => $this->newsWidget($definition, false, $hasPosts, $hasViewCounts),
            'popular-news' => $this->newsWidget($definition, true, $hasPosts, $hasViewCounts),
            'categories' => $this->categoryWidget($definition, $hasPosts && $hasCategories),
            'advertisement' => $this->advertisementWidget($definition),
            'social-follow' => $this->socialWidget($definition),
            default => null,
        })->filter()->values();

        return ['sticky' => (bool) ($configuration['sticky'] ?? false), 'widgets' => $widgets];
    }

    private function newsWidget(array $definition, bool $popular, bool $hasPosts, bool $hasViewCounts): ?SidebarWidgetData
    {
        if (! $hasPosts) {
            return null;
        }

        $limit = max(1, min((int) ($definition['limit'] ?? 6), 20));
        $query = Post::query()
            ->select(['id', 'author_id', 'title', 'slug', 'meta_title', 'featured_image', 'featured_image_alt', 'published_at', 'views_count'])
            ->published()
            ->with('primaryCategory:id,name,slug');

        if ($popular && $hasViewCounts) {
            $query->orderByDesc('views_count');
        }

        $posts = $query->orderByDesc('published_at')->orderByDesc('id')->limit($limit)->get();

        if ($posts->isEmpty()) {
            return null;
        }

        return new SidebarWidgetData(
            key: (string) $definition['key'],
            type: (string) $definition['type'],
            title: (string) ($definition['title'] ?? ''),
            items: $posts,
            showCategory: (bool) ($definition['show_category'] ?? false),
            showDate: (bool) ($definition['show_date'] ?? false),
        );
    }

    private function categoryWidget(array $definition, bool $tablesAvailable): ?SidebarWidgetData
    {
        if (! $tablesAvailable) {
            return null;
        }

        $categories = Category::query()
            ->select(['id', 'name', 'slug', 'sort_order'])
            ->active()
            ->whereHas('posts', fn (Builder $query): Builder => $query->published())
            ->withCount(['posts as published_posts_count' => fn (Builder $query): Builder => $query->published()])
            ->ordered()
            ->orderBy('id')
            ->limit(max(1, min((int) ($definition['limit'] ?? 15), 30)))
            ->get();

        if ($categories->isEmpty()) {
            return null;
        }

        return new SidebarWidgetData(
            key: (string) $definition['key'],
            type: 'categories',
            title: (string) ($definition['title'] ?? ''),
            items: $categories,
            showCount: (bool) ($definition['show_count'] ?? false),
        );
    }

    private function advertisementWidget(array $definition): ?SidebarWidgetData
    {
        $slot = (string) ($definition['slot'] ?? '');
        $advertisement = $this->advertisement($slot);

        return $advertisement->enabled ? new SidebarWidgetData(
            key: (string) $definition['key'],
            type: 'advertisement',
            advertisement: $advertisement,
        ) : null;
    }

    private function socialWidget(array $definition): ?SidebarWidgetData
    {
        $labels = ['facebook' => 'Facebook', 'x' => 'X', 'youtube' => 'YouTube', 'instagram' => 'Instagram'];
        $links = collect(config('organization.social_links', []))
            ->filter(fn (mixed $url): bool => is_string($url) && filter_var($url, FILTER_VALIDATE_URL) !== false)
            ->map(fn (string $url, string $network): array => ['network' => $network, 'label' => $labels[$network] ?? ucfirst($network), 'url' => $url])
            ->values();

        if ($links->isEmpty()) {
            return null;
        }

        return new SidebarWidgetData(
            key: (string) $definition['key'],
            type: 'social-follow',
            title: (string) ($definition['title'] ?? ''),
            items: $links,
        );
    }

    protected function supportsViewCounts(): bool
    {
        return Schema::hasColumn('posts', 'views_count');
    }
}
