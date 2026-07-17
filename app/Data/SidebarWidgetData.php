<?php

namespace App\Data;

use Illuminate\Support\Collection;

final readonly class SidebarWidgetData
{
    public function __construct(
        public string $key,
        public string $type,
        public string $title = '',
        public ?Collection $items = null,
        public ?AdvertisementData $advertisement = null,
        public bool $showCategory = false,
        public bool $showDate = false,
        public bool $showCount = false,
    ) {}
}
