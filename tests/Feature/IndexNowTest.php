<?php

namespace Tests\Feature;

use App\Jobs\SubmitIndexNowUrls;
use App\Models\Post;
use App\SEO\Sitemap\IndexNowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class IndexNowTest extends TestCase
{
    use RefreshDatabase;

    public function test_indexnow_is_disabled_by_default_and_key_file_is_not_public(): void
    {
        Http::preventStrayRequests();
        Queue::fake();
        $post = Post::factory()->published()->create();

        $this->assertFalse(app(IndexNowService::class)->submit([$post->publicUrl()]));
        Queue::assertNothingPushed();
        Http::assertNothingSent();
        $this->get('/abcdefgh.txt')->assertNotFound();
    }

    public function test_enabled_indexnow_accepts_only_local_urls_and_dispatches_a_queue_job(): void
    {
        config(['seo.indexnow.enabled' => true, 'seo.indexnow.key' => 'abcdef1234567890']);
        Queue::fake();

        $this->assertTrue(app(IndexNowService::class)->submit([route('home'), 'https://external.example/story']));
        Queue::assertPushed(SubmitIndexNowUrls::class, fn (SubmitIndexNowUrls $job): bool => $job->urls === [route('home')]);
        $this->get('/abcdef1234567890.txt')->assertOk()->assertSeeText('abcdef1234567890');
        $this->get('/different123456.txt')->assertNotFound();
    }

    public function test_job_uses_fakeable_http_and_remote_failure_does_not_throw(): void
    {
        config([
            'seo.indexnow.enabled' => true,
            'seo.indexnow.key' => 'abcdef1234567890',
            'seo.indexnow.endpoint' => 'https://api.indexnow.org/indexnow',
            'seo.indexnow.timeout' => 3,
        ]);
        Http::fake(['https://api.indexnow.org/*' => Http::response([], 500)]);

        (new SubmitIndexNowUrls([route('home')]))->handle();

        Http::assertSent(fn ($request): bool => $request->url() === 'https://api.indexnow.org/indexnow'
            && $request['host'] === 'localhost'
            && $request['urlList'] === [route('home')]);
    }

    public function test_view_count_update_does_not_dispatch_indexnow(): void
    {
        $post = Post::factory()->published()->create();
        config(['seo.indexnow.enabled' => true, 'seo.indexnow.key' => 'abcdef1234567890']);
        Queue::fake();

        $post->increment('views_count');

        Queue::assertNothingPushed();
    }
}
