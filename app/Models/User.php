<?php

namespace App\Models;

use App\Observers\SitemapObserver;
use App\Services\Users\ReferralCodeGenerator;
use App\Support\MediaUrlResolver;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[ObservedBy([SitemapObserver::class])]
#[Fillable([
    'old_wp_id',
    'name',
    'username',
    'slug',
    'email',
    'password',
    'mobile_number',
    'preferred_language',
    'avatar_path',
    'bio',
    'designation',
    'facebook_url',
    'x_url',
    'instagram_url',
    'youtube_url',
    'is_active',
    'is_public',
    'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (blank($user->refcode)) {
                $user->refcode = app(ReferralCodeGenerator::class)->generate();
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && $panel->getId() === 'admin'
            && $this->can('access admin panel');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return app(MediaUrlResolver::class)->resolve($this->avatar_path);
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    /** @return BelongsTo<User, $this> */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'ref_id');
    }

    /** @return HasMany<User, $this> */
    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'ref_id');
    }

    /** @return HasMany<User, $this> */
    public function subscriberReferrals(): HasMany
    {
        return $this->referrals()->whereHas('roles', fn (Builder $query): Builder => $query->where('name', 'subscriber'));
    }

    /** @return HasMany<Post, $this> */
    public function authoredPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'author_id');
    }

    /** @return HasMany<Post, $this> */
    public function reviewedPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'reviewed_by');
    }

    /** @return HasMany<PostVisit, $this> */
    public function postVisits(): HasMany
    {
        return $this->hasMany(PostVisit::class, 'visitor_id');
    }

    /** @return HasMany<PostBookmark, $this> */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(PostBookmark::class);
    }

    /** @return HasMany<PushSubscription, $this> */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(PushSubscription::class);
    }

    public function savedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_bookmarks')->withTimestamps();
    }

    /** @return HasMany<Post, $this> */
    public function publishedPosts(): HasMany
    {
        return $this->posts()->published();
    }

    /** @param Builder<User> $query */
    public function scopePublicAuthor(Builder $query): Builder
    {
        return $query
            ->where('is_public', true)
            ->whereNotNull('name')
            ->whereNotNull('username')
            ->whereHas('publishedPosts');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'is_public' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
