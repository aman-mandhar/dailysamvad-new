<?php

namespace App\Models;

use App\Observers\SitemapObserver;
use App\Support\MediaUrlResolver;
use Database\Factories\UserFactory;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active
            && $panel->getId() === 'admin'
            && $this->hasAnyRole(['super-admin', 'admin', 'editor']);
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
