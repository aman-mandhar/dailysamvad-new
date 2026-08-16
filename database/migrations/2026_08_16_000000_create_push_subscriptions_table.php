<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('token');
            $table->char('token_hash', 64)->unique();
            $table->uuid('device_uuid')->nullable()->index();
            $table->string('browser', 64)->nullable();
            $table->string('browser_version', 32)->nullable();
            $table->string('platform', 64)->nullable();
            $table->string('device_type', 32)->nullable();
            $table->string('language', 16)->nullable();
            $table->string('timezone', 64)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->string('permission_status', 16)->default('granted');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('last_registered_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
