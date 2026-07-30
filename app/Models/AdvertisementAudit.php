<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['advertisement_id', 'user_id', 'action', 'old_values', 'new_values', 'ip_hash'])]
class AdvertisementAudit extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public function advertisement(): BelongsTo
    {
        return $this->belongsTo(Advertisement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
