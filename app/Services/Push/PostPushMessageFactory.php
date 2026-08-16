<?php

namespace App\Services\Push;

use App\Data\Push\PushMessage;
use App\Models\Post;
use Illuminate\Support\Str;

class PostPushMessageFactory
{
    public function make(Post $post): PushMessage
    {
        $title = $this->plain($post->title);
        $source = filled($post->excerpt)
            ? $post->excerpt
            : (filled($post->meta_description) ? $post->meta_description : $post->content);
        $body = Str::limit($this->plain((string) $source), (int) config('firebase.automation.body_length', 180), '');
        if ($body === '') {
            $body = $title;
        }

        $url = $post->publicUrl();
        $image = $this->absoluteUrl($post->featured_image_url);

        return new PushMessage(
            title: Str::limit($title, 200, ''),
            body: $body,
            image: $image,
            url: $url,
            data: [
                'type' => $post->is_breaking ? 'breaking_news' : 'post',
                'entity_id' => (string) $post->getKey(),
            ],
        );
    }

    private function plain(string $value): string
    {
        $withoutExecutableText = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $value) ?? $value;

        return Str::squish(html_entity_decode(strip_tags($withoutExecutableText), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function absoluteUrl(?string $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Str::startsWith($value, ['http://', 'https://']) ? $value : url($value);
    }
}
