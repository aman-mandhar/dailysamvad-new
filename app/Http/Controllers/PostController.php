<?php

namespace App\Http\Controllers;

use App\Queries\ArticlePageQuery;
use Illuminate\Contracts\View\View;

class PostController extends Controller
{
    public function show(string $slug, ArticlePageQuery $articles): View
    {
        return view('posts.show', ['article' => $articles->find($slug)]);
    }
}
