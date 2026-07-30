<?php

namespace App\Data;

use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

final readonly class ArticleContentBlockData
{
    private function __construct(
        public string $type,
        public ?HtmlString $html = null,
        public ?AdvertisementData $advertisement = null,
        public ?Collection $advertisements = null,
    ) {}

    public static function html(string $html): self
    {
        return new self('html', new HtmlString($html));
    }

    public static function advertisement(AdvertisementData $advertisement): self
    {
        return new self('advertisement', advertisement: $advertisement);
    }

    /** @param Collection<int, AdvertisementData> $advertisements */
    public static function bottomStack(Collection $advertisements): self
    {
        return new self('advertisement_bottom_stack', advertisements: $advertisements);
    }
}
