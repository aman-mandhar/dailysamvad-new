<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_accessible_header_navigation_and_search_controls(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-header', false)
            ->assertSee('aria-label="Main navigation"', false)
            ->assertSee('href="'.route('home').'"', false)
            ->assertSee('data-mobile-menu-trigger', false)
            ->assertSee('aria-controls="ds-mobile-navigation"', false)
            ->assertSee('data-search-trigger', false)
            ->assertSee('aria-controls="ds-header-search"', false);
    }

    public function test_only_active_menu_visible_primary_categories_render(): void
    {
        Category::factory()->create(['name' => 'देश', 'slug' => 'india', 'is_active' => true, 'show_in_menu' => true]);
        Category::factory()->create(['name' => 'Hidden politics', 'slug' => 'politics', 'is_active' => true, 'show_in_menu' => false]);
        Category::factory()->create(['name' => 'Inactive business', 'slug' => 'business', 'is_active' => false, 'show_in_menu' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('देश')
            ->assertDontSee('Hidden politics')
            ->assertDontSee('Inactive business');
    }

    public function test_state_categories_render_inside_desktop_and_mobile_submenus(): void
    {
        Category::factory()->create(['name' => 'पंजाब', 'slug' => 'punjab', 'is_active' => true, 'show_in_menu' => true]);
        Category::factory()->create(['name' => 'हरियाणा', 'slug' => 'haryana', 'is_active' => true, 'show_in_menu' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('राज्य')
            ->assertSee(route('categories.show', 'punjab'))
            ->assertSee(route('categories.show', 'haryana'))
            ->assertSee('aria-controls="ds-mobile-states"', false);
    }

    public function test_policy_navigation_uses_existing_named_routes_in_required_order(): void
    {
        $content = $this->get('/')->assertOk()->getContent();
        $labels = [
            'Copyright Policy',
            'Fact-Checking Policy',
            'Editorial Policy',
            'Disclaimer',
            'Terms and Conditions',
            'Privacy Policy',
        ];

        $positions = array_map(fn (string $label): int|false => strpos($content, $label), $labels);

        $this->assertNotContains(false, $positions);
        $this->assertSame($positions, collect($positions)->sort()->values()->all());
        $this->assertStringContainsString(route('pages.privacy'), $content);
    }

    public function test_header_search_submits_expected_query_parameter_to_search_route(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('action="'.route('search').'"', false)
            ->assertSee('method="GET"', false)
            ->assertSee('name="q"', false)
            ->assertSee('data-search-input', false);
    }

    public function test_header_does_not_render_a_development_advertisement_placeholder(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('Header media slot');
    }

    public function test_header_does_not_render_advertisement_slots(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-ad-slot="HEADER_TOP"', $html);
        $this->assertStringNotContainsString('ds-header__advertisement', $html);
    }

    public function test_only_the_ninety_pixel_logo_and_navigation_row_is_sticky(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $css = file_get_contents(resource_path('css/frontend/header.css'));

        $this->assertStringContainsString('<header class="ds-header ds-header__sticky-row" data-header>', $html);
        $this->assertStringContainsString('class="ds-header__desktop-row ds-container"', $html);
        $this->assertStringContainsString(".ds-header__sticky-row {\n        position: sticky;", $css);
        $this->assertStringContainsString(".ds-header__desktop-row {\n        display: grid;\n        height: 90px;", $css);
        $this->assertStringNotContainsString(".ds-header {\n        position: sticky;", $css);
    }

    public function test_policy_navigation_has_a_fixed_height_and_cannot_expand_on_article_pages(): void
    {
        $css = file_get_contents(resource_path('css/frontend/header.css'));

        $this->assertStringContainsString(".ds-policy-nav {\n        height: 43px;\n        overflow: hidden;", $css);
        $this->assertStringContainsString(".ds-policy-nav__scroller {\n        display: flex;\n        height: 43px;", $css);
    }
}
