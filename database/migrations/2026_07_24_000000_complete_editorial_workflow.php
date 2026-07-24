<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->foreignId('submitted_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('review_assigned_at')->nullable()->after('submitted_by')->index();
            $table->timestamp('review_started_at')->nullable()->after('review_assigned_at')->index();
            $table->timestamp('corrections_requested_at')->nullable()->after('review_notes');
            $table->foreignId('corrections_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('correction_notes')->nullable();
            $table->timestamp('approved_at')->nullable()->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->index();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable()->index();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::create('post_workflow_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['post_id', 'created_at']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_workflow_events');
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('submitted_by');
            $table->dropColumn(['review_assigned_at', 'review_started_at']);
            $table->dropConstrainedForeignId('corrections_requested_by');
            $table->dropColumn(['corrections_requested_at', 'correction_notes', 'approved_at']);
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn('rejected_at');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn('rejection_reason');
            $table->dropConstrainedForeignId('scheduled_by');
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn('archived_at');
            $table->dropConstrainedForeignId('archived_by');
        });
    }
};
