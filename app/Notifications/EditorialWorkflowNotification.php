<?php

namespace App\Notifications;

use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EditorialWorkflowNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Post $post,
        public readonly string $event,
        public readonly ?string $message = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'post_id' => $this->post->getKey(),
            'event' => $this->event,
            'title' => $this->post->title,
            'message' => $this->message,
            'url' => route('filament.admin.resources.posts.edit', ['record' => $this->post]),
        ];
    }
}
