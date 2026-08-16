<?php

namespace Tests\Feature\Push;

use App\Contracts\Push\AccessTokenProvider;
use App\Data\Push\PushMessage;
use App\Services\Push\FirebaseMessagingClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class FirebaseMessagingClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('firebase.messaging.project_id', 'test-project');
        Http::preventStrayRequests();
    }

    public function test_it_sends_an_http_v1_webpush_payload_and_captures_message_id(): void
    {
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/test-project/messages/123'])]);
        $client = new FirebaseMessagingClient(new FakeAccessTokenProvider);
        $message = new PushMessage(
            'Headline',
            'Body copy',
            image: 'https://example.test/image.jpg',
            url: 'https://example.test/news/story',
            icon: 'https://example.test/icon.png',
            data: ['type' => 'test', 'count' => 2, 'featured' => true],
        );

        $result = $client->send('fake-fcm-token', $message);

        $this->assertTrue($result->success);
        $this->assertSame('projects/test-project/messages/123', $result->messageId);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://fcm.googleapis.com/v1/projects/test-project/messages:send'
                && $request->hasHeader('Authorization', 'Bearer fake-oauth-token')
                && $request['message']['token'] === 'fake-fcm-token'
                && $request['message']['notification']['title'] === 'Headline'
                && $request['message']['notification']['image'] === 'https://example.test/image.jpg'
                && $request['message']['webpush']['fcm_options']['link'] === 'https://example.test/news/story'
                && $request['message']['data'] === ['type' => 'test', 'count' => '2', 'featured' => 'true', 'url' => 'https://example.test/news/story'];
        });
    }

    public function test_it_classifies_unregistered_invalid_auth_server_and_network_failures(): void
    {
        $provider = new FakeAccessTokenProvider;
        $client = new FirebaseMessagingClient($provider);
        $message = new PushMessage('Title', 'Body');

        Http::fake(['fcm.googleapis.com/*' => Http::response([
            'error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]],
        ], 404)]);
        $invalid = $client->send('token', $message);
        $this->assertTrue($invalid->tokenInvalid);
        $this->assertFalse($invalid->retryable);

        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'INVALID_ARGUMENT']], 400)]);
        $payload = $client->send('token', $message);
        $this->assertFalse($payload->tokenInvalid);
        $this->assertFalse($payload->retryable);

        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fakeSequence()->push(['error' => ['status' => 'UNAUTHENTICATED']], 401)->push(['error' => ['status' => 'PERMISSION_DENIED']], 403);
        $auth = $client->send('token', $message);
        $this->assertFalse($auth->tokenInvalid);
        $this->assertFalse($auth->retryable);
        $this->assertSame(1, $provider->forgets);

        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(['fcm.googleapis.com/*' => Http::response(['error' => ['status' => 'UNAVAILABLE']], 503)]);
        $server = $client->send('token', $message);
        $this->assertTrue($server->retryable);
        $this->assertFalse($server->tokenInvalid);

        Http::swap(new Factory);
        Http::preventStrayRequests();
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $network = $client->send('token', $message);
        $this->assertTrue($network->retryable);
        $this->assertFalse($network->tokenInvalid);
    }

    public function test_push_message_rejects_invalid_urls_and_nested_data(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new PushMessage('Title', 'Body', url: '/relative', data: ['nested' => ['not allowed']]);
    }
}

class FakeAccessTokenProvider implements AccessTokenProvider
{
    public int $forgets = 0;

    public function token(): string
    {
        return 'fake-oauth-token';
    }

    public function forget(): void
    {
        $this->forgets++;
    }
}
