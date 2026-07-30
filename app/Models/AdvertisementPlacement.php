<?php

namespace App\Models;

use App\Observers\AdvertisementPlacementObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['advertisement_id', 'position', 'page_type', 'category_id', 'tag_id', 'post_id', 'device'])]
#[ObservedBy([AdvertisementPlacementObserver::class])]
class AdvertisementPlacement extends Model
{
    public function advertisement(): BelongsTo
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
