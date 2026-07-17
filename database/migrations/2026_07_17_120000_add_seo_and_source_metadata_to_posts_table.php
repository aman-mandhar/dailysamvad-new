<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('scheduled_at');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('focus_keyword')->nullable()->index()->after('meta_description');
            $table->text('canonical_url')->nullable()->after('focus_keyword');
            $table->text('old_url')->nullable()->after('canonical_url');
            $table->text('source_url')->nullable()->after('old_url');
            $table->string('source_name')->nullable()->index()->after('source_url');
            $table->json('source_data')->nullable()->after('source_name');
            $table->json('seo_data')->nullable()->after('source_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_focus_keyword_index');
            $table->dropIndex('posts_source_name_index');

            $table->dropColumn([
                'meta_title',
                'meta_description',
                'focus_keyword',
                'canonical_url',
                'old_url',
                'source_url',
                'source_name',
                'source_data',
                'seo_data',
            ]);
        });
    }
};
