<?php

namespace App\Models;

use Database\Factories\PushNotificationDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['push_notification_id', 'push_subscription_id', 'subscription_token_hash', 'status', 'queued_at'])]
class PushNotificationDelivery extends Model
{
    /** @use HasFactory<PushNotificationDeliveryFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(fn (PushNotificationDelivery $delivery) => $delivery->public_id ??= (string) Str::uuid());
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(PushNotification::class, 'push_notification_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(PushSubscription::class, 'push_subscription_id');
    }

    protected function casts(): array
    {
        return [
            'retryable' => 'boolean',
            'queued_at' => 'datetime',
            'attempted_at' => 'datetime',
            'last_attempted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'failed_at' => 'datetime',
            'first_clicked_at' => 'datetime',
            'last_clicked_at' => 'datetime',
            'attempt_count' => 'integer',
            'click_count' => 'integer',
        ];
    }
}
