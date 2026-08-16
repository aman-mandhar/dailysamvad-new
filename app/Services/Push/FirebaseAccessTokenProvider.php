<?php

namespace App\Services\Push;

use App\Contracts\Push\AccessTokenProvider;
use App\Exceptions\Push\FirebaseAuthenticationException;
use App\Exceptions\Push\FirebaseConfigurationException;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use JsonException;
use Throwable;

class FirebaseAccessTokenProvider implements AccessTokenProvider
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function __construct(private readonly Repository $cache) {}

    public function token(): string
    {
        $credentials = $this->credentials();
        $cacheKey = $this->cacheKey($credentials);
        $cached = $this->cache->get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            return Cache::lock($cacheKey.':refresh', max(1, (int) config('firebase.messaging.oauth_lock_seconds', 15)))
                ->block(5, function () use ($cacheKey, $credentials): string {
                    $cached = $this->cache->get($cacheKey);
                    if (is_string($cached) && $cached !== '') {
                        return $cached;
                    }

                    return $this->refresh($cacheKey, $credentials);
                });
        } catch (FirebaseAuthenticationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new FirebaseAuthenticationException('Unable to obtain a Firebase OAuth access token.', previous: $exception);
        }
    }

    public function forget(): void
    {
        $this->cache->forget($this->cacheKey($this->credentials()));
    }

    /** @param array<string, mixed> $credentials */
    private function refresh(string $cacheKey, array $credentials): string
    {
        try {
            $result = (new ServiceAccountCredentials(self::SCOPE, $credentials))->fetchAuthToken();
        } catch (Throwable $exception) {
            throw new FirebaseAuthenticationException('Unable to obtain a Firebase OAuth access token.', previous: $exception);
        }

        $token = $result['access_token'] ?? null;
        if (! is_string($token) || $token === '') {
            throw new FirebaseAuthenticationException('Google OAuth did not return an access token.');
        }

        $lifetime = max(60, (int) ($result['expires_in'] ?? 3600) - (int) config('firebase.messaging.token_expiry_margin', 300));
        $this->cache->put($cacheKey, $token, $lifetime);

        return $token;
    }

    /** @return array<string, mixed> */
    private function credentials(): array
    {
        $projectId = config('firebase.messaging.project_id');
        $path = config('firebase.messaging.service_account_path');

        if (! is_string($projectId) || trim($projectId) === '') {
            throw new FirebaseConfigurationException('Firebase messaging project ID is not configured.');
        }
        if (! is_string($path) || trim($path) === '' || ! is_file($path) || ! is_readable($path)) {
            throw new FirebaseConfigurationException('Firebase service-account file is missing or unreadable.');
        }

        try {
            $json = file_get_contents($path);
            $credentials = json_decode($json === false ? '' : $json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new FirebaseConfigurationException('Firebase service-account file is not valid JSON.');
        }

        foreach (['client_email', 'private_key', 'token_uri'] as $field) {
            if (! is_array($credentials) || ! is_string($credentials[$field] ?? null) || trim($credentials[$field]) === '') {
                throw new FirebaseConfigurationException("Firebase service-account field {$field} is missing.");
            }
        }

        return $credentials;
    }

    /** @param array<string, mixed> $credentials */
    private function cacheKey(array $credentials): string
    {
        return 'firebase:fcm:access-token:'.hash('sha256', (string) config('firebase.messaging.project_id').'|'.$credentials['client_email']);
    }
}
