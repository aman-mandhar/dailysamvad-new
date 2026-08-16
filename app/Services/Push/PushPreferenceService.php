<?php

namespace App\Services\Push;

use App\Models\PushSubscription;
use App\Models\PushTopic;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class PushPreferenceService
{
    public function resolve(string $token, string $deviceUuid): PushSubscription
    {
        return PushSubscription::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('device_uuid', $deviceUuid)
            ->active()
            ->firstOrFail();
    }

    /** @return array{configured:bool,selected_topic_ids:array<int,int>,topics:array<int,array{id:int,name:string,type:string}>} */
    public function state(PushSubscription $subscription): array
    {
        return [
            'configured' => $subscription->preferences_configured_at !== null,
            'selected_topic_ids' => $subscription->topics()->active()->pluck('push_topics.id')->map(fn ($id): int => (int) $id)->all(),
            'topics' => PushTopic::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name', 'type'])->map(fn (PushTopic $topic): array => [
                'id' => (int) $topic->getKey(),
                'name' => $topic->name,
                'type' => $topic->type,
            ])->all(),
        ];
    }

    /** @param array<int, int|string> $topicIds */
    public function sync(PushSubscription $subscription, array $topicIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $topicIds)));
        $valid = PushTopic::query()->active()->whereKey($ids)->pluck('id')->map(fn ($id): int => (int) $id)->all();
        if (count($valid) !== count($ids)) {
            throw (new ModelNotFoundException)->setModel(PushTopic::class, $ids);
        }

        DB::transaction(function () use ($subscription, $valid): void {
            $locked = PushSubscription::query()->whereKey($subscription->getKey())->lockForUpdate()->firstOrFail();
            $locked->topics()->sync($valid);
            $locked->forceFill(['preferences_configured_at' => now()])->save();
        });

        return $this->state($subscription->fresh());
    }
}
