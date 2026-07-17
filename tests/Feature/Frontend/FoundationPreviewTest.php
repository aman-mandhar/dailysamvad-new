<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;

class FoundationPreviewTest extends TestCase
{
    public function test_preview_view_renders_foundation_primitives(): void
    {
        $html = view('frontend.foundation-preview')->render();

        $this->assertStringContainsString('Daily Samvad frontend foundation', $html);
        $this->assertStringContainsString('ds-main-grid', $html);
    }

    public function test_preview_route_is_not_available_in_testing(): void
    {
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('frontend.foundation-preview'));
    }
}
