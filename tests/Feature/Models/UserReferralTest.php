<?php

namespace Tests\Feature\Models;

use App\Models\User;
use App\Services\Users\ReferralCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_receives_a_unique_referral_code_automatically(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->assertMatchesRegularExpression('/^DS[A-Z0-9]{8}$/', $first->refcode);
        $this->assertNotSame($first->refcode, $second->refcode);
    }

    public function test_existing_supplied_code_is_preserved_for_controlled_creation(): void
    {
        $user = User::factory()->make();
        $user->refcode = 'DSMANUAL01';
        $user->save();

        $this->assertSame('DSMANUAL01', $user->fresh()->refcode);
    }

    public function test_generator_retries_a_collision(): void
    {
        $existing = User::factory()->make();
        $existing->refcode = 'DSCOLLIDE';
        $existing->save();

        $generator = new class extends ReferralCodeGenerator
        {
            private int $attempt = 0;

            protected function candidate(): string
            {
                return $this->attempt++ === 0 ? 'DSCOLLIDE' : 'DSUNIQUE01';
            }
        };

        $this->assertSame('DSUNIQUE01', $generator->generate());
    }

    public function test_referrer_relationships_work_and_deletion_nulls_the_foreign_key(): void
    {
        $referrer = User::factory()->create();
        $referred = User::factory()->create();
        $referred->ref_id = $referrer->getKey();
        $referred->save();

        $this->assertTrue($referred->referrer->is($referrer));
        $this->assertTrue($referrer->referrals->contains($referred));

        $referrer->delete();

        $this->assertDatabaseHas('users', ['id' => $referred->getKey(), 'ref_id' => null]);
    }

    public function test_invalid_referrer_foreign_key_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $user = User::factory()->make();
        $user->ref_id = 999999;
        $user->save();
    }

    public function test_backfill_is_idempotent_and_does_not_overwrite_existing_codes(): void
    {
        $valid = User::factory()->create();
        $validCode = $valid->refcode;
        $attributes = User::factory()->raw();
        DB::table('users')->insert([
            ...$attributes,
            'refcode' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_07_20_000100_backfill_user_referral_codes.php');
        $migration->up();
        $migration->up();

        $this->assertSame($validCode, $valid->fresh()->refcode);
        $this->assertSame(0, User::query()->whereNull('refcode')->count());
        $this->assertSame(User::count(), User::query()->distinct()->count('refcode'));
    }
}
