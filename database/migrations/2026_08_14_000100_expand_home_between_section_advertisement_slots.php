<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('advertisement_placements')
            ->where('position', 'HOME_BETWEEN_SECTIONS')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $id, int $index): void {
                DB::table('advertisement_placements')
                    ->where('id', $id)
                    ->update(['position' => 'HOME_BETWEEN_SECTIONS_'.min($index + 1, 5)]);
            });
    }

    public function down(): void
    {
        DB::table('advertisement_placements')
            ->whereIn('position', [
                'HOME_BETWEEN_SECTIONS_1',
                'HOME_BETWEEN_SECTIONS_2',
                'HOME_BETWEEN_SECTIONS_3',
                'HOME_BETWEEN_SECTIONS_4',
                'HOME_BETWEEN_SECTIONS_5',
            ])
            ->update(['position' => 'HOME_BETWEEN_SECTIONS']);
    }
};
