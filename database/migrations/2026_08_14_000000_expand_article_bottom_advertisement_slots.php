<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('advertisement_placements')
            ->where('position', 'ARTICLE_BOTTOM')
            ->update(['position' => 'ARTICLE_BOTTOM_1']);
    }

    public function down(): void
    {
        DB::table('advertisement_placements')
            ->where('position', 'ARTICLE_BOTTOM_1')
            ->update(['position' => 'ARTICLE_BOTTOM']);
    }
};
