<?php

namespace App\Enums;

enum AdvertisementStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Paused = 'paused';
    case Expired = 'expired';
    case Archived = 'archived';

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => ucfirst($case->value)])->all();
    }
}
