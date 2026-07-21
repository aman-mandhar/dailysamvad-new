<?php

use App\Models\User;
use App\Services\Users\ReferralCodeGenerator;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $generator = app(ReferralCodeGenerator::class);

        User::query()
            ->whereNull('refcode')
            ->select(['id', 'refcode'])
            ->eachById(function (User $user) use ($generator): void {
                $user->forceFill(['refcode' => $generator->generate()])->updateQuietly();
            }, 100);
    }

    public function down(): void
    {
        // Referral codes are durable public identifiers and are not erased on rollback.
    }
};
