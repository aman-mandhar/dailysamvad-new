<?php

namespace App\Models;

use App\Enums\AdvertisementStatus;
use App\Observers\AdvertisementObserver;
use Database\Factories\AdvertisementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[ObservedBy([AdvertisementObserver::class])]
#[Fillable(['title', 'slug', 'advertiser_name', 'description', 'status', 'priority', 'rotation_weight', 'target_url', 'open_in_new_tab', 'nofollow', 'sponsored', 'start_at', 'end_at', 'created_by', 'updated_by', 'published_by', 'published_at'])]
class Advertisement extends Model
{
    /** @use HasFactory<AdvertisementFactory> */
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $advertisement): void {
            $advertisement->uuid ??= (string) Str::uuid();
            $advertisement->slug = $advertisement->slug ?: Str::slug($advertisement->title).'-'.Str::lower(Str::random(6));
        });
    }

    protected function casts(): array
    {
        return ['status' => AdvertisementStatus::class, 'priority' => 'integer', 'rotation_weight' => 'integer', 'open_in_new_tab' => 'boolean', 'nofollow' => 'boolean', 'sponsored' => 'boolean', 'start_at' => 'datetime', 'end_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function creative(): HasOne
    {
        return $this->hasOne(AdvertisementCreative::class)->latestOfMany();
    }

    public function creatives(): HasMany
    {
        return $this->hasMany(AdvertisementCreative::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(AdvertisementPlacement::class);
    }

    public function dailyStats(): HasMany
    {
        return $this->hasMany(AdvertisementDailyStat::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(AdvertisementAudit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [AdvertisementStatus::Active->value, AdvertisementStatus::Scheduled->value]);
    }

    public function scopeCurrentlyScheduled(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('start_at')->orWhere('start_at', '<=', now()))->where(fn (Builder $q) => $q->whereNull('end_at')->orWhere('end_at', '>', now()));
    }

    public function scopeForPosition(Builder $query, string $position): Builder
    {
        return $query->whereHas('placements', fn (Builder $q) => $q->where('position', $position));
    }

    public function isRenderable(): bool
    {
        return in_array($this->status, [AdvertisementStatus::Active, AdvertisementStatus::Scheduled], true)
            && ($this->start_at === null || $this->start_at->lte(now())) && ($this->end_at === null || $this->end_at->gt(now()));
    }

    public function getImpressionsAttribute(): int
    {
        return (int) ($this->dailyStats()->sum('impressions'));
    }

    public function getClicksAttribute(): int
    {
        return (int) ($this->dailyStats()->sum('clicks'));
    }

    public function getCtrAttribute(): float
    {
        return $this->impressions > 0 ? round($this->clicks / $this->impressions * 100, 2) : 0.0;
    }
}
