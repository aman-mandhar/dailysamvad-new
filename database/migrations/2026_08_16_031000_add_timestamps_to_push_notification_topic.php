<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('push_notification_topic') && ! Schema::hasColumn('push_notification_topic', 'created_at')) {
            Schema::table('push_notification_topic', function (Blueprint $table): void {
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('push_notification_topic', 'created_at')) {
            Schema::table('push_notification_topic', function (Blueprint $table): void {
                $table->dropTimestamps();
            });
        }
    }
};
