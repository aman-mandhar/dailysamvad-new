<?php

namespace Tests\Feature\Push;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class FirebaseFoundationTest extends TestCase
{
    public function test_firebase_web_configuration_has_only_browser_client_fields(): void
    {
        $this->assertSame([
            'api_key',
            'auth_domain',
            'project_id',
            'storage_bucket',
            'messaging_sender_id',
            'app_id',
            'measurement_id',
            'vapid_key',
        ], array_keys(config('firebase.web')));
    }

    public function test_push_opt_in_component_renders_without_firebase_credentials(): void
    {
        $html = Blade::render('<x-frontend.push-notification-opt-in />');

        $this->assertStringContainsString('data-push-opt-in', $html);
        $this->assertStringContainsString('data-push-enable', $html);
        $this->assertStringContainsString('Enable Notifications', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
    }

    public function test_push_opt_in_primary_button_has_a_defined_brand_color(): void
    {
        $tokens = file_get_contents(resource_path('css/frontend/tokens.css'));
        $footer = file_get_contents(resource_path('css/frontend/footer.css'));

        $this->assertStringContainsString('--ds-color-brand:', $tokens);
        $this->assertStringContainsString('background: var(--ds-color-brand)', $footer);
    }

    public function test_messaging_service_worker_exists_at_the_public_root(): void
    {
        $this->assertFileExists(public_path('firebase-messaging-sw.js'));
    }
}
