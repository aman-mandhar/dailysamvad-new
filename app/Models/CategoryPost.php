<?php

namespace App\Models;

use App\Observers\SitemapPivotObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Relations\Pivot;

#[ObservedBy([SitemapPivotObserver::class])]
class CategoryPost extends Pivot
{
    public $timestamps = false;
}
