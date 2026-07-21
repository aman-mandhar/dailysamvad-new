<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->foreignId('reviewed_by')->nullable()->after('author_id')->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable()->after('scheduled_at')->index();
            $table->timestamp('reviewed_at')->nullable()->after('submitted_at')->index();
            $table->text('review_notes')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['submitted_at']);
            $table->dropIndex(['reviewed_at']);
            $table->dropColumn(['submitted_at', 'reviewed_at', 'review_notes']);
        });
    }
};
