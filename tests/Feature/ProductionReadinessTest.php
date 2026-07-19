<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_is_minimal_and_available(): void
    {
        $this->get('/up')
            ->assertOk()
            ->assertSee('Application up')
            ->assertDontSee('APP_KEY')
            ->assertDontSee(base_path());
    }

    public function test_public_responses_include_conservative_security_headers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_critical_error_pages_use_database_independent_layout(): void
    {
        foreach ([403, 419, 429, 500, 503] as $status) {
            $html = view("errors.$status")->render();

            $this->assertStringContainsString("Error $status", $html);
            $this->assertStringContainsString('Return to homepage', $html);
            $this->assertStringNotContainsString('ds-header', $html);
            $this->assertStringNotContainsString('globalBreakingNews', $html);
        }
    }

    public function test_development_preview_is_not_registered_in_production(): void
    {
        $this->assertFalse(app()->environment('production'));
        $this->assertNull(Route::getRoutes()->getByName('frontend.foundation-preview'));

        $routeFile = file_get_contents(base_path('routes/web.php'));
        $this->assertStringContainsString("app()->environment(['local', 'development'])", $routeFile);
    }
}
