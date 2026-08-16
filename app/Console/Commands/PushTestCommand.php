<?php

namespace App\Console\Commands;

use App\Contracts\Push\AccessTokenProvider;
use App\Data\Push\PushMessage;
use App\Exceptions\Push\FirebaseAuthenticationException;
use App\Exceptions\Push\FirebaseConfigurationException;
use App\Models\PushSubscription;
use App\Services\Push\PushNotificationService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class PushTestCommand extends Command
{
    protected $signature = 'push:test
        {--subscription= : Explicit push subscription database ID}
        {--title=Daily Samvad Push Test : Notification title}
        {--body=Push notification engine is working. : Notification body}
        {--url= : Optional absolute click URL}
        {--image= : Optional absolute image URL}
        {--check-config : Validate configuration and OAuth without sending}
        {--force : Allow a test send in production}';

    protected $description = 'Send a test notification to one explicitly selected push subscription.';

    public function handle(PushNotificationService $push, AccessTokenProvider $accessTokens): int
    {
        try {
            if ($this->option('check-config')) {
                $accessTokens->token();
                $this->info('Firebase messaging configuration and OAuth authentication are valid.');

                return self::SUCCESS;
            }

            $id = filter_var($this->option('subscription'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false) {
                $this->error('Provide one subscription with --subscription=<id>. No notification was sent.');

                return self::INVALID;
            }
            if (app()->isProduction() && ! $this->option('force')) {
                $this->error('Production test sends require --force. No notification was sent.');

                return self::FAILURE;
            }

            $subscription = PushSubscription::query()->find($id);
            if ($subscription === null) {
                $this->error("Subscription {$id} was not found. No notification was sent.");

                return self::FAILURE;
            }

            $message = new PushMessage(
                (string) $this->option('title'),
                (string) $this->option('body'),
                image: $this->stringOption('image'),
                url: $this->stringOption('url'),
            );
            $result = $push->sendToSubscription($subscription, $message);
        } catch (FirebaseConfigurationException|FirebaseAuthenticationException|InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->line("Subscription: {$id}");
        $this->line('Result: '.($result->success ? 'success' : 'failure'));
        if ($result->success) {
            $this->line('FCM message ID: '.$result->messageId);
        } else {
            $this->line('Classification: '.($result->errorCode ?? 'UNKNOWN'));
            $this->line('Retryable: '.($result->retryable ? 'yes' : 'no'));
        }

        return $result->success ? self::SUCCESS : self::FAILURE;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
