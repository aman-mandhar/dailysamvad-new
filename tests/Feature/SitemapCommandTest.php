<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_validation_warm_and_clear_commands_complete_without_remote_requests(): void
    {
        Post::factory()->published()->create();

        $this->artisan('seo:sitemaps:validate')->expectsOutput('Sitemap XML and robots policy are valid locally.')->assertSuccessful();
        $this->artisan('seo:sitemaps:warm')->expectsOutput('Sitemap caches warmed.')->assertSuccessful();
        $this->artisan('seo:sitemaps:clear')->expectsOutput('Sitemap caches invalidated.')->assertSuccessful();
    }
}
