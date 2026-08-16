<?php

namespace App\Services\Push;

use App\Models\Category;
use App\Models\PushTopic;

class PushTopicSyncService
{
    /** @return array{created:int,updated:int,deactivated:int} */
    public function sync(): array
    {
        $counts = ['created' => 0, 'updated' => 0, 'deactivated' => 0];
        $categoryIds = [];

        Category::query()->select(['id', 'name', 'slug', 'sort_order', 'is_active'])->chunkById(500, function ($categories) use (&$counts, &$categoryIds): void {
            foreach ($categories as $category) {
                $categoryIds[] = $category->getKey();
                $topic = PushTopic::query()->firstOrNew(['category_id' => $category->getKey()]);
                $created = ! $topic->exists;
                $topic->fill([
                    'name' => $category->name,
                    'slug' => 'category-'.$category->getKey(),
                    'type' => 'category',
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                ]);
                if ($created || $topic->isDirty()) {
                    $topic->save();
                    $counts[$created ? 'created' : 'updated']++;
                }
            }
        });

        $counts['deactivated'] = PushTopic::query()->where('type', 'category')->where('is_active', true)->where(fn ($query) => $query->whereNull('category_id')->orWhereNotIn('category_id', $categoryIds))->update(['is_active' => false]);

        $breaking = PushTopic::query()->firstOrNew(['slug' => 'breaking-news']);
        $created = ! $breaking->exists;
        $breaking->fill(['name' => 'Breaking News', 'type' => 'system', 'is_active' => true, 'is_default' => true, 'sort_order' => 0]);
        if ($created || $breaking->isDirty()) {
            $breaking->save();
            $counts[$created ? 'created' : 'updated']++;
        }

        return $counts;
    }
}
