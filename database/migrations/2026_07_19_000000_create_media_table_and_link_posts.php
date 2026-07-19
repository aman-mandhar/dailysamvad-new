<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('old_wp_id')->nullable()->unique();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->text('original_url')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->text('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['disk', 'path']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('featured_media_id')->nullable()->after('featured_image')->constrained('media')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('featured_media_id');
        });
        Schema::dropIfExists('media');
    }
};
