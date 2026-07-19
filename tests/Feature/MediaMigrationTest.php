<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_migrations_are_additive_and_constraints_are_present(): void
    {
        $this->assertTrue(Schema::hasTable('media'));
        $this->assertTrue(Schema::hasColumns('media', [
            'old_wp_id', 'disk', 'path', 'original_url', 'original_filename', 'mime_type', 'size',
            'width', 'height', 'checksum', 'alt_text', 'caption', 'credit', 'copyright',
            'uploaded_by', 'missing_at', 'metadata', 'deleted_at',
        ]));
        $this->assertTrue(Schema::hasColumn('posts', 'featured_media_id'));
    }
}
