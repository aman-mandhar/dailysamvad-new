<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Jobs\PublishScheduledPost;
use App\Models\Post;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Post::query()->dueForPublishing()->select('id')->orderBy('id')->chunkById(100, function ($posts): void {
        $posts->each(fn (Post $post) => PublishScheduledPost::dispatch($post->getKey()));
    });
})->name('publish-scheduled-posts')->everyMinute()->withoutOverlapping();
