<?php

namespace Tests\Feature\Frontend;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YouTubePlaylistPlayerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('youtube.playlist_id', 'PLHZXkx2rzrVUxs_--bZGvq-KkStA_OTGq');
        config()->set('youtube.api_key', null);
    }

    public function test_homepage_and_article_each_render_the_player_once_in_the_shared_sidebar(): void
    {
        $post = Post::factory()->published()->create();

        $homepage = $this->get('/')->assertOk()->getContent();
        $article = $this->get($post->publicUrl())->assertOk()->getContent();

        $this->assertSame(1, substr_count($homepage, 'data-youtube-playlist-player'));
        $this->assertSame(1, substr_count($article, 'data-youtube-playlist-player'));
        $this->assertStringContainsString('data-player-placement="homepage"', $homepage);
        $this->assertStringContainsString('data-player-placement="article"', $article);
    }

    public function test_component_renders_configured_playlist_and_never_exposes_the_api_key(): void
    {
        config()->set('youtube.api_key', 'super-secret-server-key');
        Http::fake(['www.googleapis.com/*' => Http::response([
            'items' => [[
                'snippet' => ['title' => 'Newest', 'publishedAt' => '2026-07-20T00:00:00Z'],
                'contentDetails' => ['videoId' => 'latestVid01', 'videoPublishedAt' => '2026-07-20T00:00:00Z'],
                'status' => ['privacyStatus' => 'public'],
            ]],
        ])]);

        $html = Blade::render('<x-youtube-playlist-player placement="homepage" />');

        $this->assertStringContainsString(config('youtube.playlist_id'), $html);
        $this->assertStringContainsString('latestVid01', $html);
        $this->assertStringContainsString('youtube-nocookie.com', $html);
        $this->assertStringNotContainsString('super-secret-server-key', $html);
    }

    public function test_multiple_instances_have_unique_player_ids(): void
    {
        $html = Blade::render('<x-youtube-playlist-player /><x-youtube-playlist-player placement="article" />');

        preg_match_all('/<iframe\s+id="(youtube-playlist-player-[^"]+)"/', $html, $matches);

        $this->assertCount(2, $matches[1]);
        $this->assertCount(2, array_unique($matches[1]));
    }

    public function test_missing_or_invalid_playlist_configuration_renders_nothing_without_crashing(): void
    {
        config()->set('youtube.playlist_id', 'invalid playlist id');

        $this->assertSame('', trim(Blade::render('<x-youtube-playlist-player />')));
    }
}
