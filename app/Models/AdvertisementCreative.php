<?php

namespace App\Models;

use App\Observers\AdvertisementCreativeObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['advertisement_id', 'type', 'media_id', 'image_path', 'video_path', 'poster_media_id', 'poster_path', 'html_code', 'alt_text', 'caption', 'width', 'height', 'mime_type', 'file_size', 'autoplay', 'muted', 'loop', 'controls'])]
#[ObservedBy([AdvertisementCreativeObserver::class])]
class AdvertisementCreative extends Model
{
    protected function casts(): array
    {
        return ['autoplay' => 'boolean', 'muted' => 'boolean', 'loop' => 'boolean', 'controls' => 'boolean', 'width' => 'integer', 'height' => 'integer', 'file_size' => 'integer'];
    }

    public function advertisement(): BelongsTo
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    public function posterMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'poster_media_id');
    }
}
