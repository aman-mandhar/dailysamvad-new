<?php

namespace App\Data;

use Illuminate\Support\HtmlString;

final readonly class ArticleContentBlockData
{
    private function __construct(
        public string $type,
        public ?HtmlString $html = null,
        public ?AdvertisementData $advertisement = null,
    ) {}

    public static function html(string $html): self
    {
        return new self('html', new HtmlString($html));
    }

    public static function advertisement(AdvertisementData $advertisement): self
    {
        return new self('advertisement', advertisement: $advertisement);
    }
}
