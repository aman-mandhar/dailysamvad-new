<?php

namespace App\Models;

use Database\Factories\PushTopicFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['category_id', 'name', 'slug', 'type', 'is_active', 'is_default', 'sort_order'])]
class PushTopic extends Model
{
    /** @use HasFactory<PushTopicFactory> */
    use HasFactory;

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(PushSubscription::class, 'push_subscription_topic')->withTimestamps();
    }

    public function notifications(): BelongsToMany
    {
        return $this->belongsToMany(PushNotification::class, 'push_notification_topic')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_default' => 'boolean', 'sort_order' => 'integer'];
    }
}
