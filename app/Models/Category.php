<?php

namespace App\Models;

use App\Observers\SitemapObserver;
use App\Support\MediaUrlResolver;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([SitemapObserver::class])]
#[Fillable([
    'old_wp_id',
    'parent_id',
    'name',
    'slug',
    'description',
    'image_path',
    'meta_title',
    'meta_description',
    'sort_order',
    'is_active',
    'show_in_menu',
])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public function getImageUrlAttribute(): ?string
    {
        return app(MediaUrlResolver::class)->resolve($this->image_path);
    }

    /**
     * Get the parent category.
     *
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get the child categories.
     *
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get the category's posts.
     *
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->withPivot('is_primary');
    }

    public function pushTopic(): HasOne
    {
        return $this->hasOne(PushTopic::class);
    }

    /**
     * Scope the query to active categories.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope the query by display order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Scope the query to categories configured for menu display.
     */
    public function scopeMenuVisible(Builder $query): Builder
    {
        return $query->where('show_in_menu', true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'show_in_menu' => 'boolean',
        ];
    }
}
