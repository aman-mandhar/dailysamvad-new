<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('original_filename')->nullable()->after('original_url');
            $table->string('checksum', 64)->nullable()->after('height')->index();
            $table->string('credit')->nullable()->after('caption');
            $table->string('copyright')->nullable()->after('credit');
            $table->foreignId('uploaded_by')->nullable()->after('copyright')->constrained('users')->nullOnDelete();
            $table->timestamp('missing_at')->nullable()->after('uploaded_by')->index();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropForeign(['uploaded_by']);
            $table->dropIndex(['checksum']);
            $table->dropIndex(['missing_at']);
            $table->dropColumn(['original_filename', 'checksum', 'credit', 'copyright', 'uploaded_by', 'missing_at', 'deleted_at']);
        });
    }
};
