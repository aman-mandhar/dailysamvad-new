<?php

namespace App\Models;

use App\Observers\SitemapPivotObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[ObservedBy([SitemapPivotObserver::class])]
class PostTag extends Pivot
{
    public $timestamps = false;
}
