<?php

namespace Tests\Feature;

use App\Support\MediaPathNormalizer;
use App\Support\MediaUrlResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUrlResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        config()->set('media.disk', 'public');
    }

    public function test_local_and_wordpress_paths_resolve_to_the_canonical_public_url(): void
    {
        $resolver = app(MediaUrlResolver::class);

        $this->assertSame('/storage/wordpress/uploads/2026/07/photo.jpg', $resolver->resolve('storage/wordpress//uploads/2026/07/photo.jpg'));
        $this->assertSame('/storage/wordpress/uploads/2026/07/photo.jpg', $resolver->resolve('https://dailysamvad.com/wp-content/uploads/2026/07/photo.jpg'));
        $this->assertSame('/storage/wordpress/uploads/2026/07/photo.jpg', $resolver->resolve('wordpress\\uploads\\2026\\07\\photo.jpg'));
    }

    public function test_allowed_remote_url_and_query_string_remain_unchanged(): void
    {
        $url = 'https://images.example.com/news/photo.jpg?v=2&width=800';

        $this->assertSame($url, app(MediaUrlResolver::class)->resolve($url));
    }

    public function test_malformed_traversal_and_executable_values_fail_safely(): void
    {
        $resolver = app(MediaUrlResolver::class);

        foreach (['javascript:alert(1)', '../secret.jpg', '%2e%2e/secret.jpg', 'C:\\secret.jpg', 'uploads/shell.php'] as $value) {
            $this->assertNull($resolver->resolve($value));
        }
    }

    public function test_unicode_filename_is_preserved_safely(): void
    {
        $path = 'wordpress/uploads/2026/07/समाचार चित्र.jpg';

        $this->assertSame('/storage/'.$path, app(MediaUrlResolver::class)->resolve($path));
    }

    public function test_missing_media_is_null_only_for_explicit_existence_checks(): void
    {
        $resolver = app(MediaUrlResolver::class);

        $this->assertSame('/storage/wordpress/uploads/missing.jpg', $resolver->resolve('wordpress/uploads/missing.jpg'));
        $this->assertNull($resolver->resolveExisting('wordpress/uploads/missing.jpg'));

        Storage::disk('public')->put('wordpress/uploads/present.jpg', 'image');
        $this->assertSame('/storage/wordpress/uploads/present.jpg', $resolver->resolveExisting('wordpress/uploads/present.jpg'));
    }

    public function test_normalization_and_resolution_make_no_database_queries(): void
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        app(MediaPathNormalizer::class)->normalize('wordpress/uploads/photo.jpg');
        app(MediaUrlResolver::class)->resolve('wordpress/uploads/photo.jpg');

        $this->assertCount(0, DB::getQueryLog());
    }
}
