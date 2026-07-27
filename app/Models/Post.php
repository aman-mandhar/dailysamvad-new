<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Observers\PostObserver;
use App\Support\MediaUrlResolver;
use App\Support\ResponsiveImageData;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[ObservedBy([PostObserver::class])]
#[Fillable([
    'old_wp_id',
    'author_id',
    'title',
    'slug',
    'excerpt',
    'content',
    'featured_image',
    'featured_media_id',
    'featured_image_alt',
    'featured_image_caption',
    'status',
    'language',
    'is_breaking',
    'is_featured',
    'allow_comments',
    'published_at',
    'scheduled_at',
    'meta_title',
    'meta_description',
    'focus_keyword',
    'canonical_url',
    'old_url',
    'source_url',
    'source_name',
    'source_data',
    'seo_data',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, SoftDeletes;

    private ?string $featuredImageBeforeUpdate = null;

    private bool $featuredImageUrlResolved = false;

    private ?string $resolvedFeaturedImageUrl = null;

    public function setSlugAttribute(mixed $value): void
    {
        $this->attributes['slug'] = trim((string) $value);
    }

    /**
     * Get the public URL for an existing featured image.
     */
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if ($this->featuredImageUrlResolved) {
            return $this->resolvedFeaturedImageUrl;
        }

        $this->featuredImageUrlResolved = true;

        return $this->resolvedFeaturedImageUrl = app(MediaUrlResolver::class)->resolve($this->featured_image);
    }

    /** @return array{src: ?string, srcset: ?string, width: ?int, height: ?int} */
    public function responsiveFeaturedImage(): array
    {
        $media = $this->relationLoaded('featuredMedia') ? $this->getRelation('featuredMedia') : null;

        return app(ResponsiveImageData::class)->for($this->featured_image, $media);
    }

    public function rememberFeaturedImageBeforeUpdate(?string $path): void
    {
        $this->featuredImageBeforeUpdate = $path;
    }

    public function pullFeaturedImageBeforeUpdate(): ?string
    {
        $path = $this->featuredImageBeforeUpdate;
        $this->featuredImageBeforeUpdate = null;

        return $path;
    }

    /**
     * Get the post's author.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function workflowEvents(): HasMany
    {
        return $this->hasMany(PostWorkflowEvent::class)->orderBy('created_at')->orderBy('id');
    }

    /** @return HasMany<PostVisit, $this> */
    public function visits(): HasMany
    {
        return $this->hasMany(PostVisit::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(PostBookmark::class);
    }

    public function bookmarkedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_bookmarks')->withTimestamps();
    }

    /** @return BelongsTo<Media, $this> */
    public function featuredMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    /**
     * Get the post's categories.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->using(CategoryPost::class)->withPivot('is_primary');
    }

    /**
     * Get the post's tags.
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->using(PostTag::class);
    }

    /**
     * Get the post's category marked as primary.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function primaryCategory(): BelongsToMany
    {
        return $this->categories()->wherePivot('is_primary', true);
    }

    /**
     * Get the explicit SEO title or fall back to the post title.
     */
    public function effectiveMetaTitle(): string
    {
        return filled($this->meta_title) ? $this->meta_title : $this->title;
    }

    /**
     * Get the best available plain-text SEO description.
     */
    public function effectiveMetaDescription(): string
    {
        if (filled($this->meta_description)) {
            return $this->meta_description;
        }

        if (filled($this->excerpt)) {
            return $this->excerpt;
        }

        $plainText = html_entity_decode(
            strip_tags($this->content),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        return Str::limit(Str::squish($plainText), 160, '');
    }

    /**
     * Get the explicit canonical URL without inferring a route.
     */
    public function effectiveCanonicalUrl(): ?string
    {
        return filled($this->canonical_url) ? $this->canonical_url : null;
    }

    /** @return array{year: string, month: string, slug: string} */
    public function publicRouteParameters(): array
    {
        $date = $this->published_at ?? $this->scheduled_at ?? $this->created_at ?? now();

        return [
            'year' => $date->format('Y'),
            'month' => $date->format('m'),
            'slug' => trim($this->slug),
        ];
    }

    public function publicUrl(): string
    {
        return route('news.show', $this->publicRouteParameters());
    }

    /**
     * Scope the query to currently published posts.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', PostStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** @param Builder<Post> $query */
    public function scopeIndexable(Builder $query): Builder
    {
        return $query->whereRaw("(seo_data IS NULL OR JSON_EXTRACT(seo_data, '$.robots.index') IS NULL OR JSON_EXTRACT(seo_data, '$.robots.index') != false)");
    }

    /**
     * Scope the query to draft posts.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Draft->value);
    }

    /**
     * Scope the query to posts awaiting their scheduled publication time.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query
            ->where('status', PostStatus::Scheduled->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now());
    }

    public function scopeDueForPublishing(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Scheduled->value)->whereNotNull('scheduled_at')->where('scheduled_at', '<=', now());
    }

    public function scopeAssignedTo(Builder $query, User|int $reviewer): Builder
    {
        return $query->where('reviewed_by', $reviewer instanceof User ? $reviewer->getKey() : $reviewer);
    }

    /**
     * Scope the query to breaking posts.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeBreaking(Builder $query): Builder
    {
        return $query->where('is_breaking', true);
    }

    /**
     * Scope the query to featured posts.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope the query to published posts ordered newest first.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeLatestPublished(Builder $query): Builder
    {
        return $query->published()->orderByDesc('published_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'is_breaking' => 'boolean',
            'is_featured' => 'boolean',
            'allow_comments' => 'boolean',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'review_assigned_at' => 'datetime',
            'review_started_at' => 'datetime',
            'corrections_requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'archived_at' => 'datetime',
            'source_data' => 'array',
            'seo_data' => 'array',
        ];
    }
}
