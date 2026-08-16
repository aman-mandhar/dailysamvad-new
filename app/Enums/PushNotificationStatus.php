<?php

namespace App\Enums;

enum PushNotificationStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Draft->value => 'Draft',
            self::Queued->value => 'Queued',
            self::Sent->value => 'Sent',
            self::Failed->value => 'Failed',
        ];
    }
}
