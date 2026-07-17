<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Observers\PostObserver;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

    /**
     * Get the post's categories.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('is_primary');
    }

    /**
     * Get the post's tags.
     *
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
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
            'source_data' => 'array',
            'seo_data' => 'array',
        ];
    }
}
