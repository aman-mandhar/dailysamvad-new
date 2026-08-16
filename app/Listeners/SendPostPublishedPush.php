<?php

namespace App\Listeners;

use App\Events\PostPublished;
use App\Services\Push\PostPublishPushAutomation;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPostPublishedPush
{
    public function __construct(private readonly PostPublishPushAutomation $automation) {}

    public function handle(PostPublished $event): void
    {
        try {
            $this->automation->dispatch($event->postId);
        } catch (Throwable $exception) {
            Log::warning('Post-published push listener failed safely.', [
                'post_id' => $event->postId,
                'event' => 'post_published',
                'exception' => $exception::class,
            ]);
        }
    }
}
