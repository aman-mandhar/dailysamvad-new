<?php

namespace App\Services\Push;

use App\Models\Post;
use App\Models\PushTopic;

class PostPushTopicResolver
{
    /** @return array<int, int> */
    public function ids(Post $post): array
    {
        $categoryIds = $post->categories()->pluck('categories.id');
        $ids = PushTopic::query()->active()->where('type', 'category')->whereIn('category_id', $categoryIds)->pluck('id');

        if ($post->is_breaking) {
            $breakingId = PushTopic::query()->active()->where('slug', 'breaking-news')->value('id');
            if ($breakingId !== null) {
                $ids->push($breakingId);
            }
        }

        return $ids->map(fn ($id): int => (int) $id)->unique()->values()->all();
    }
}
