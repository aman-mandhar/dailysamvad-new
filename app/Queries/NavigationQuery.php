<?php

namespace App\Queries;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class NavigationQuery
{
    /** @var array<string, string> */
    private const MAIN_ITEMS = [
        'india' => 'देश',
        'world' => 'दुनिया',
        'politics' => 'राजनीति',
        'business' => 'बिजनेस',
        'education' => 'एजुकेशन',
        'entertainment' => 'मनोरंजन',
    ];

    /** @var array<string, string> */
    private const STATE_ITEMS = [
        'punjab' => 'पंजाब',
        'haryana' => 'हरियाणा',
        'himachal-pradesh' => 'हिमाचल प्रदेश',
        'uttarakhand' => 'उत्तराखंड',
        'uttar-pradesh' => 'उत्तर प्रदेश',
        'delhi' => 'दिल्ली',
        'jammu-kashmir' => 'जम्मू-कश्मीर',
    ];

    /**
     * Return the audited primary navigation structure from visible categories.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function mainMenu(): Collection
    {
        if (! Schema::hasTable('categories')) {
            return collect();
        }

        $categories = Category::query()
            ->select(['id', 'parent_id', 'name', 'slug', 'sort_order'])
            ->active()
            ->menuVisible()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $bySlug = $categories->keyBy(fn (Category $category): string => $this->normalizedSlug($category->slug));
        $stateParent = $categories->first(fn (Category $category): bool => in_array(
            $this->normalizedSlug($category->slug),
            ['state', 'states', 'rajya'],
            true,
        ) || trim($category->name) === 'राज्य');
        $states = $stateParent
            ? $categories
                ->where('parent_id', $stateParent->getKey())
                ->sortBy([['sort_order', 'asc'], ['name', 'asc']])
                ->map(fn (Category $category): array => $this->item($category, $category->name))
                ->values()
            : $this->orderedItems(self::STATE_ITEMS, $bySlug);
        $items = collect();

        foreach (self::MAIN_ITEMS as $slug => $label) {
            if ($slug === 'politics') {
                $items->push([
                    'label' => 'राज्य',
                    'slug' => null,
                    'url' => null,
                    'children' => $states,
                ]);
            }

            if ($category = $bySlug->get($slug)) {
                $items->push($this->item($category, $label));
            }
        }

        return $items;
    }

    /**
     * @param  array<string, string>  $order
     * @param  Collection<string, Category>  $categories
     * @return Collection<int, array<string, mixed>>
     */
    private function orderedItems(array $order, Collection $categories): Collection
    {
        return collect($order)
            ->map(fn (string $label, string $slug): ?array => ($category = $categories->get($slug))
                ? $this->item($category, $label)
                : null)
            ->filter()
            ->values();
    }

    /** @return array<string, mixed> */
    private function item(Category $category, string $fallbackLabel): array
    {
        return [
            'label' => filled($category->name) ? $category->name : $fallbackLabel,
            'slug' => $category->slug,
            'url' => route('categories.show', $category->slug),
            'children' => collect(),
        ];
    }

    private function normalizedSlug(string $slug): string
    {
        return trim(strtolower(rawurldecode($slug)), '/');
    }
}
