<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('push_notifications', 'source_type')) {
            Schema::table('push_notifications', function (Blueprint $table): void {
                $table->string('source_type', 20)->default('manual')->after('post_id')->index();
                $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                $table->unique(['source_type', 'source_id']);
            });
        }

        if (! Schema::hasTable('push_notification_deliveries')) {
            Schema::create('push_notification_deliveries', function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id')->unique();
                $table->foreignId('push_notification_id')->constrained()->cascadeOnDelete();
                $table->foreignId('push_subscription_id')->nullable()->constrained()->nullOnDelete();
                $table->char('subscription_token_hash', 64)->nullable();
                $table->string('status', 20)->default('queued')->index();
                $table->unsignedInteger('attempt_count')->default(0);
                $table->string('fcm_message_id', 512)->nullable();
                $table->unsignedSmallInteger('http_status')->nullable();
                $table->string('error_code', 100)->nullable();
                $table->string('error_category', 40)->nullable()->index();
                $table->string('error_message', 500)->nullable();
                $table->boolean('retryable')->default(false)->index();
                $table->timestamp('queued_at')->nullable();
                $table->timestamp('attempted_at')->nullable();
                $table->timestamp('last_attempted_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamp('first_clicked_at')->nullable();
                $table->timestamp('last_clicked_at')->nullable();
                $table->unsignedBigInteger('click_count')->default(0);
                $table->timestamps();

                $table->unique(['push_notification_id', 'push_subscription_id'], 'push_delivery_notification_subscription_unique');
                $table->index(['push_notification_id', 'status'], 'push_delivery_notification_status_idx');
                $table->index(['push_notification_id', 'first_clicked_at'], 'push_delivery_notification_click_idx');
                $table->index(['created_at', 'status'], 'push_delivery_created_status_idx');
            });
        } else {
            Schema::table('push_notification_deliveries', function (Blueprint $table): void {
                if (! Schema::hasIndex('push_notification_deliveries', ['push_notification_id', 'first_clicked_at'])) {
                    $table->index(['push_notification_id', 'first_clicked_at'], 'push_delivery_notification_click_idx');
                }
                if (! Schema::hasIndex('push_notification_deliveries', ['created_at', 'status'])) {
                    $table->index(['created_at', 'status'], 'push_delivery_created_status_idx');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_deliveries');
        Schema::table('push_notifications', function (Blueprint $table): void {
            $table->dropUnique(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
