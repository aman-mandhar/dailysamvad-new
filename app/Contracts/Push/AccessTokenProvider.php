<?php

namespace App\Contracts\Push;

interface AccessTokenProvider
{
    public function token(): string;

    public function forget(): void;
}
