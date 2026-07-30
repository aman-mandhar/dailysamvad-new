<?php

use App\Enums\AdvertisementPosition;

return [
    'reading_speed_words_per_minute' => 220,
    'related_limit' => 6,
    'inline_ad_positions' => [
        AdvertisementPosition::ArticleAfterParagraph1->value => 1,
        AdvertisementPosition::ArticleAfterParagraph2->value => 2,
        AdvertisementPosition::ArticleAfterParagraph3->value => 3,
        AdvertisementPosition::ArticleAfterParagraph4->value => 4,
        AdvertisementPosition::ArticleAfterParagraph5->value => 5,
    ],
];
