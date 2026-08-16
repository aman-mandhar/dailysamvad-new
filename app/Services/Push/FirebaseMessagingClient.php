<?php

namespace App\Services\Push;

use App\Contracts\Push\AccessTokenProvider;
use App\Contracts\Push\PushTransport;
use App\Data\Push\PushDeliveryResult;
use App\Data\Push\PushMessage;
use App\Exceptions\Push\FirebaseConfigurationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class FirebaseMessagingClient implements PushTransport
{
    public function __construct(private readonly AccessTokenProvider $accessTokens) {}

    public function send(string $token, PushMessage $message): PushDeliveryResult
    {
        $projectId = config('firebase.messaging.project_id');
        if (! is_string($projectId) || trim($projectId) === '') {
            throw new FirebaseConfigurationException('Firebase messaging project ID is not configured.');
        }

        try {
            $response = $this->request($projectId, $token, $message);
            if ($response->status() === 401) {
                $this->accessTokens->forget();
                $response = $this->request($projectId, $token, $message);
            }
        } catch (ConnectionException) {
            return PushDeliveryResult::failure('NETWORK_ERROR', 'Firebase could not be reached.', retryable: true);
        }

        if ($response->successful()) {
            $messageId = $response->json('name');

            return is_string($messageId) && $messageId !== ''
                ? PushDeliveryResult::success($messageId, $response->status())
                : PushDeliveryResult::failure('INVALID_RESPONSE', 'Firebase returned no message ID.', $response->status(), retryable: true);
        }

        return $this->failureResult($response);
    }

    private function request(string $projectId, string $token, PushMessage $message): Response
    {
        return Http::acceptJson()
            ->withToken($this->accessTokens->token())
            ->timeout((int) config('firebase.messaging.timeout', 10))
            ->connectTimeout((int) config('firebase.messaging.connect_timeout', 5))
            ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                'message' => $this->payload($token, $message),
            ]);
    }

    /** @return array<string, mixed> */
    private function payload(string $token, PushMessage $message): array
    {
        $notification = array_filter([
            'title' => $message->title,
            'body' => $message->body,
            'image' => $message->image,
        ], fn (mixed $value): bool => $value !== null);

        $webNotification = array_filter([
            'icon' => $message->icon ?? config('firebase.messaging.default_icon'),
            'image' => $message->image,
        ], fn (mixed $value): bool => is_string($value) && $value !== '');

        $payload = ['token' => $token, 'notification' => $notification];
        $data = $message->data;
        if ($message->url !== null) {
            $data['url'] = $message->url;
        }
        if ($data !== []) {
            $payload['data'] = array_map(fn (mixed $value): string => match (true) {
                is_bool($value) => $value ? 'true' : 'false',
                $value === null => '',
                default => (string) $value,
            }, $data);
        }
        if ($webNotification !== [] || $message->url !== null) {
            $payload['webpush'] = array_filter([
                'notification' => $webNotification,
                'fcm_options' => $message->url === null ? null : ['link' => $message->url],
            ], fn (mixed $value): bool => $value !== null && $value !== []);
        }

        return $payload;
    }

    private function failureResult(Response $response): PushDeliveryResult
    {
        $status = $response->status();
        $errorStatus = $response->json('error.status');
        $errorCode = is_string($errorStatus) ? $errorStatus : 'FCM_ERROR';

        foreach ((array) $response->json('error.details', []) as $detail) {
            if (is_array($detail) && is_string($detail['errorCode'] ?? null)) {
                $errorCode = $detail['errorCode'];
                break;
            }
        }

        $tokenInvalid = $errorCode === 'UNREGISTERED';
        $retryable = ! $tokenInvalid && ($status === 429 || $status >= 500 || in_array($errorCode, [
            'RESOURCE_EXHAUSTED', 'QUOTA_EXCEEDED', 'UNAVAILABLE', 'INTERNAL',
        ], true));

        $safeMessage = match ($errorCode) {
            'UNREGISTERED' => 'The Firebase registration token is no longer registered.',
            'UNAUTHENTICATED', 'PERMISSION_DENIED' => 'Firebase authorization failed.',
            'RESOURCE_EXHAUSTED', 'QUOTA_EXCEEDED' => 'Firebase quota was exceeded.',
            'UNAVAILABLE', 'INTERNAL' => 'Firebase is temporarily unavailable.',
            default => 'Firebase rejected the push message.',
        };

        return PushDeliveryResult::failure($errorCode, $safeMessage, $status, $tokenInvalid, $retryable);
    }
}
