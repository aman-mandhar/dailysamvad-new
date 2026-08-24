<?php

namespace Tests\Feature\Frontend;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LayoutEngineTest extends TestCase
{
    public function test_homepage_layout_renders_with_header_and_footer(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<header', false)
            ->assertSee('aria-label="Main navigation"', false)
            ->assertSee('<main id="main-content"', false)
            ->assertSee('<footer', false)
            ->assertSee('DailySamvad');
    }

    public function test_frontend_layout_renders_configured_audience_measurement_scripts(): void
    {
        config([
            'services.mgid.site_id' => '629948',
            'services.google_analytics.measurement_id' => 'G-K596NQV45Z',
            'services.comscore.client_id' => '41132432',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('https://jsc.mgid.com/site/629948.js', false)
            ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-K596NQV45Z', false)
            ->assertSee("gtag('config', 'G-K596NQV45Z')", false)
            ->assertSee('https://sb.scorecardresearch.com/cs/41132432/beacon.js', false);
    }

    public function test_empty_breaking_news_collection_renders_without_errors_or_bar(): void
    {
        $html = Blade::render('<x-frontend.breaking-news :items="collect()" />');

        $this->assertSame('', trim($html));
    }
}
