<?php

namespace App\Models;

use Database\Factories\PostVisitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'post_id',
    'visitor_id',
    'visitor_uuid',
    'session_id',
    'ip_address',
    'user_agent',
    'referrer_url',
    'source',
    'medium',
    'campaign',
    'device_type',
    'browser',
    'platform',
    'country',
    'region',
    'city',
    'visited_at',
])]
class PostVisit extends Model
{
    /** @use HasFactory<PostVisitFactory> */
    use HasFactory;

    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /** @return BelongsTo<User, $this> */
    public function visitor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'visitor_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }
}
