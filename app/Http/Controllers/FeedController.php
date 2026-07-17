<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Response;

class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $posts = Post::query()
            ->select(['id', 'author_id', 'title', 'slug', 'excerpt', 'content', 'featured_image', 'published_at'])
            ->published()
            ->with(['author:id,name', 'primaryCategory:id,name,slug'])
            ->orderByDesc('published_at')
            ->limit((int) config('publication.feed_limit', 50))
            ->get();

        return response()->view('feeds.rss', compact('posts'), 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}
