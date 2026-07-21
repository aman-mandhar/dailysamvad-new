<?php

namespace App\Services\Users;

use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class ReferralCodeGenerator
{
    public function __construct(
        private readonly int $maximumAttempts = 10,
    ) {}

    public function generate(): string
    {
        for ($attempt = 0; $attempt < $this->maximumAttempts; $attempt++) {
            $code = $this->candidate();

            if (! User::query()->where('refcode', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to generate a unique referral code after '.$this->maximumAttempts.' attempts.');
    }

    protected function candidate(): string
    {
        return 'DS'.Str::upper(Str::random(8));
    }
}
