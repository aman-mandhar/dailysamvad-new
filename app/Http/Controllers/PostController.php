<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Queries\ArticlePageQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PostController extends Controller
{
    public function show(string $year, string $month, string $slug, ArticlePageQuery $articles): View
    {
        $article = $articles->find($slug);
        abort_unless(
            $article->post->published_at?->format('Y') === $year
            && $article->post->published_at?->format('m') === $month,
            404,
        );

        return view('posts.show', ['article' => $article]);
    }

    public function legacy(string $slug): RedirectResponse
    {
        $post = Post::query()->published()->where('slug', $slug)->firstOrFail(['slug', 'published_at']);

        return redirect()->route('news.show', $post->publicRouteParameters(), 301);
    }
}
