<?php

namespace App\Models;

use App\Observers\SitemapObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([SitemapObserver::class])]
#[Fillable(['old_wp_id', 'disk', 'path', 'original_url', 'original_filename', 'mime_type', 'size', 'width', 'height', 'checksum', 'alt_text', 'caption', 'credit', 'copyright', 'uploaded_by', 'missing_at', 'metadata'])]
class Media extends Model
{
    use HasFactory, SoftDeletes;

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return HasMany<Post, $this> */
    public function featuredPosts(): HasMany
    {
        return $this->hasMany(Post::class, 'featured_media_id');
    }

    protected function casts(): array
    {
        return ['size' => 'integer', 'width' => 'integer', 'height' => 'integer', 'missing_at' => 'datetime', 'metadata' => 'array'];
    }
}
