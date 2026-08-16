<?php

use App\Enums\PostStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->timestamp('push_notified_at')->nullable()->index()->after('published_at');
        });

        DB::table('posts')
            ->where('status', PostStatus::Published->value)
            ->whereNotNull('published_at')
            ->update(['push_notified_at' => DB::raw('published_at')]);
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn('push_notified_at');
        });
    }
};
