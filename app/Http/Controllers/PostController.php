<?php

namespace App\Http\Controllers;

use App\Queries\ArticleQuery;
use App\Support\TrustedArticleHtml;
use Illuminate\Contracts\View\View;

class PostController extends Controller
{
    public function show(string $slug, ArticleQuery $articles, TrustedArticleHtml $html): View
    {
        $data = $articles->find($slug);
        $data['articleContent'] = $html->sanitize($data['post']->content);

        return view('posts.show', $data);
    }
}
