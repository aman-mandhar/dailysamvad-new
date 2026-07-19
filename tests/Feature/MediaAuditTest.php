<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_is_chunked_read_only_and_reports_integrity_states(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        Storage::disk('public')->put('media/present.jpg', 'image');
        Storage::disk('public')->put('media/derived.jpg', 'derivative');
        $present = Media::query()->create(['disk' => 'public', 'path' => 'media/present.jpg', 'mime_type' => 'image/jpeg', 'width' => 100, 'height' => 50]);
        $missing = Media::query()->create([
            'disk' => 'public', 'path' => 'media/missing.jpg', 'mime_type' => 'image/jpeg',
            'metadata' => ['derivatives' => [['path' => 'media/derived.jpg', 'width' => 100, 'verified_at' => now()->toAtomString()]]],
        ]);
        Post::factory()->create(['featured_media_id' => $present->id, 'featured_image' => $present->path]);

        $this->assertSame(0, Artisan::call('media:audit', ['--chunk' => 1, '--no-storage-scan' => true]));
        $output = Artisan::output();

        $this->assertStringContainsString('records: 2', $output);
        $this->assertStringContainsString('missing originals: 1', $output);
        $this->assertStringContainsString('derivative without original: 1', $output);
        $this->assertStringContainsString('No files or records were deleted', $output);
        $this->assertDatabaseHas('media', ['id' => $missing->id, 'path' => 'media/missing.jpg']);
        $this->assertCount(1, Storage::disk('local')->allFiles('media-audits'));
    }
}
