<?php

namespace App\Contracts\Push;

use App\Data\Push\PushDeliveryResult;
use App\Data\Push\PushMessage;

interface PushTransport
{
    public function send(string $token, PushMessage $message): PushDeliveryResult;
}
