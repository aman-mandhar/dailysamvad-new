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

    public function test_empty_breaking_news_collection_renders_without_errors_or_bar(): void
    {
        $html = Blade::render('<x-frontend.breaking-news :items="collect()" />');

        $this->assertSame('', trim($html));
    }
}
