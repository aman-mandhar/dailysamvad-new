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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('old_wp_id')->nullable()->unique()->after('id');
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('slug')->nullable()->unique()->after('username');
            $table->string('mobile_number', 20)->nullable()->after('password');
            $table->string('avatar_path')->nullable()->after('mobile_number');
            $table->text('bio')->nullable()->after('avatar_path');
            $table->string('designation')->nullable()->after('bio');
            $table->string('facebook_url')->nullable()->after('designation');
            $table->string('x_url')->nullable()->after('facebook_url');
            $table->string('instagram_url')->nullable()->after('x_url');
            $table->string('youtube_url')->nullable()->after('instagram_url');
            $table->boolean('is_active')->default(true)->index()->after('youtube_url');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_old_wp_id_unique');
            $table->dropUnique('users_username_unique');
            $table->dropUnique('users_slug_unique');
            $table->dropIndex('users_is_active_index');

            $table->dropColumn([
                'old_wp_id',
                'username',
                'slug',
                'mobile_number',
                'avatar_path',
                'bio',
                'designation',
                'facebook_url',
                'x_url',
                'instagram_url',
                'youtube_url',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
