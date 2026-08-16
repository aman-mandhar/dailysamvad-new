<?php

namespace App\Services\Push;

use App\Models\Post;
use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Builder;

class PushAudienceResolver
{
    public function __construct(private readonly PostPushTopicResolver $postTopics) {}

    /** @return Builder<PushSubscription> */
    public function allActive(): Builder
    {
        return PushSubscription::query()->active();
    }

    /** @param array<int, int|string> $topicIds
     * @return Builder<PushSubscription>
     */
    public function forTopics(array $topicIds, bool $includeLegacy = false): Builder
    {
        $ids = array_values(array_unique(array_map('intval', $topicIds)));
        if ($ids === [] && ! $includeLegacy) {
            return $this->allActive()->whereRaw('1 = 0');
        }

        return $this->allActive()->where(function (Builder $query) use ($ids, $includeLegacy): void {
            if ($includeLegacy) {
                $query->whereNull('preferences_configured_at');
            }
            if ($ids !== []) {
                $method = $includeLegacy ? 'orWhereHas' : 'whereHas';
                $query->{$method}('topics', fn (Builder $topics): Builder => $topics->active()->whereKey($ids));
            }
        });
    }

    /** @return Builder<PushSubscription> */
    public function forPost(Post $post): Builder
    {
        return $this->forTopics($this->postTopics->ids($post), includeLegacy: true);
    }
}
