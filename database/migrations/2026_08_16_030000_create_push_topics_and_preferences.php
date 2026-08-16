<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_topics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 20)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique('category_id');
        });

        Schema::create('push_subscription_topic', function (Blueprint $table): void {
            $table->foreignId('push_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('push_topic_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['push_subscription_id', 'push_topic_id']);
            $table->index(['push_topic_id', 'push_subscription_id']);
        });

        Schema::table('push_subscriptions', function (Blueprint $table): void {
            $table->timestamp('preferences_configured_at')->nullable()->after('unsubscribed_at')->index();
        });

        Schema::table('push_notifications', function (Blueprint $table): void {
            $table->string('target_type', 20)->default('all')->after('target_url')->index();
        });

        Schema::create('push_notification_topic', function (Blueprint $table): void {
            $table->foreignId('push_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('push_topic_id')->constrained()->restrictOnDelete();
            $table->primary(['push_notification_id', 'push_topic_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_notification_topic');
        Schema::table('push_notifications', fn (Blueprint $table) => $table->dropColumn('target_type'));
        Schema::table('push_subscriptions', fn (Blueprint $table) => $table->dropColumn('preferences_configured_at'));
        Schema::dropIfExists('push_subscription_topic');
        Schema::dropIfExists('push_topics');
    }
};
