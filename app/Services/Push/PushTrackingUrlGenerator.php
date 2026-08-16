<?php

namespace App\Services\Push;

use App\Models\PushNotificationDelivery;

class PushTrackingUrlGenerator
{
    public function forDelivery(PushNotificationDelivery $delivery): string
    {
        return route('push.click', ['publicId' => $delivery->public_id]);
    }
}
