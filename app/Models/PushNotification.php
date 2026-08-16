<?php

namespace App\Models;

use App\Enums\PushNotificationStatus;
use Database\Factories\PushNotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['post_id', 'title', 'body', 'image_url', 'target_url', 'target_type', 'source_type', 'source_id'])]
class PushNotification extends Model
{
    /** @use HasFactory<PushNotificationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (PushNotification $notification): void {
            $notification->status ??= PushNotificationStatus::Draft;
            $notification->target_type ??= 'all';
            $notification->source_type ??= 'manual';

            if ($notification->created_by === null && auth()->check()) {
                $notification->created_by = auth()->id();
            }
        });
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(PushTopic::class, 'push_notification_topic')->withTimestamps();
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(PushNotificationDelivery::class);
    }

    /** @param Builder<PushNotification> $query */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PushNotificationStatus::Draft);
    }

    public function isDraft(): bool
    {
        return $this->status === PushNotificationStatus::Draft;
    }

    protected function casts(): array
    {
        return [
            'status' => PushNotificationStatus::class,
            'recipient_count' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
