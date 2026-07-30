<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advertisements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('advertiser_name')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('status')->default('draft')->index();
            $table->integer('priority')->default(0)->index();
            $table->unsignedInteger('rotation_weight')->default(1);
            $table->text('target_url')->nullable();
            $table->boolean('open_in_new_tab')->default(true);
            $table->boolean('nofollow')->default(true);
            $table->boolean('sponsored')->default(true);
            $table->timestamp('start_at')->nullable()->index();
            $table->timestamp('end_at')->nullable()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'start_at', 'end_at', 'priority'], 'ads_resolution_index');
        });

        Schema::create('advertisement_creatives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advertisement_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->foreignId('media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->string('video_path')->nullable();
            $table->foreignId('poster_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('poster_path')->nullable();
            $table->longText('html_code')->nullable();
            $table->text('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->boolean('autoplay')->default(false);
            $table->boolean('muted')->default(true);
            $table->boolean('loop')->default(false);
            $table->boolean('controls')->default(true);
            $table->timestamps();
            $table->index(['advertisement_id', 'type']);
        });

        Schema::create('advertisement_placements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advertisement_id')->constrained()->cascadeOnDelete();
            $table->string('position')->index();
            $table->string('page_type')->nullable()->index();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('device')->default('all')->index();
            $table->timestamps();
            $table->index(['position', 'page_type', 'device'], 'ad_placement_resolution_index');
        });

        Schema::create('advertisement_daily_stats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advertisement_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('unique_impressions')->default(0);
            $table->unsignedBigInteger('unique_clicks')->default(0);
            $table->timestamps();
            $table->unique(['advertisement_id', 'date']);
        });

        Schema::create('advertisement_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('advertisement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advertisement_audits');
        Schema::dropIfExists('advertisement_daily_stats');
        Schema::dropIfExists('advertisement_placements');
        Schema::dropIfExists('advertisement_creatives');
        Schema::dropIfExists('advertisements');
    }
};
