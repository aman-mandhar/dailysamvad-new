<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->text('featured_image_alt')->nullable()->change();
            $table->text('featured_image_caption')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->string('featured_image_alt')->nullable()->change();
            $table->string('featured_image_caption')->nullable()->change();
        });
    }
};
