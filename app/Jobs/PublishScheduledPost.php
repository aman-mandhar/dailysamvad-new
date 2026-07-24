<?php

namespace App\Jobs;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Services\EditorialWorkflowService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PublishScheduledPost implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;
    public array $backoff = [30, 120];
    public int $uniqueFor = 300;

    public function __construct(public int $postId)
    {
        $this->onQueue('publishing');
    }

    public function uniqueId(): string { return (string) $this->postId; }

    public function handle(EditorialWorkflowService $workflow): void
    {
        $post = Post::query()->find($this->postId);
        if (! $post || $post->status !== PostStatus::Scheduled || ! $post->scheduled_at || $post->scheduled_at->isFuture()) return;
        $workflow->publishScheduled($post);
    }
}
