<?php

namespace App\Models;

use Database\Factories\PushSubscriptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'token',
    'token_hash',
    'device_uuid',
    'browser',
    'browser_version',
    'platform',
    'device_type',
    'language',
    'timezone',
    'user_agent',
    'permission_status',
    'is_active',
    'last_seen_at',
    'last_registered_at',
    'unsubscribed_at',
])]
#[Hidden(['token'])]
class PushSubscription extends Model
{
    /** @use HasFactory<PushSubscriptionFactory> */
    use HasFactory;

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(PushTopic::class, 'push_subscription_topic')->withTimestamps();
    }

    public function pushDeliveries(): HasMany
    {
        return $this->hasMany(PushNotificationDelivery::class);
    }

    /** @param Builder<PushSubscription> $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** @param Builder<PushSubscription> $query */
    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        return $query->where('user_id', $user instanceof User ? $user->getKey() : $user);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
            'last_registered_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'preferences_configured_at' => 'datetime',
        ];
    }
}
