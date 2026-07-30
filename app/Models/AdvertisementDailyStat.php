<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['advertisement_id', 'date', 'impressions', 'clicks', 'unique_impressions', 'unique_clicks'])]
class AdvertisementDailyStat extends Model
{
    protected function casts(): array
    {
        return ['date' => 'date', 'impressions' => 'integer', 'clicks' => 'integer', 'unique_impressions' => 'integer', 'unique_clicks' => 'integer'];
    }

    public function advertisement(): BelongsTo
    {
        return $this->belongsTo(Advertisement::class);
    }
}
