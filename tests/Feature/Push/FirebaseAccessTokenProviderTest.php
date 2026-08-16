<?php

namespace Tests\Feature\Push;

use App\Exceptions\Push\FirebaseConfigurationException;
use App\Services\Push\FirebaseAccessTokenProvider;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FirebaseAccessTokenProviderTest extends TestCase
{
    public function test_it_returns_a_cached_token_without_contacting_google(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'firebase-test-');
        file_put_contents($path, json_encode([
            'client_email' => 'push-test@example.test',
            'private_key' => 'not-a-real-private-key',
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ], JSON_THROW_ON_ERROR));
        config()->set('firebase.messaging.project_id', 'cache-project');
        config()->set('firebase.messaging.service_account_path', $path);
        $key = 'firebase:fcm:access-token:'.hash('sha256', 'cache-project|push-test@example.test');
        Cache::put($key, 'cached-oauth-token', 60);

        try {
            $provider = app(FirebaseAccessTokenProvider::class);
            $this->assertSame('cached-oauth-token', $provider->token());
            $provider->forget();
            $this->assertNull(Cache::get($key));
        } finally {
            unlink($path);
        }
    }

    public function test_it_reports_invalid_credential_json_without_leaking_contents(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'firebase-test-');
        file_put_contents($path, '{invalid');
        config()->set('firebase.messaging.project_id', 'test-project');
        config()->set('firebase.messaging.service_account_path', $path);

        try {
            $this->expectException(FirebaseConfigurationException::class);
            $this->expectExceptionMessage('not valid JSON');
            app(FirebaseAccessTokenProvider::class)->token();
        } finally {
            unlink($path);
        }
    }
}
