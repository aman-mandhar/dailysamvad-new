<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Queries\SearchQuery;
use Illuminate\Contracts\View\View;

class SearchController extends Controller
{
    public function __invoke(SearchRequest $request, SearchQuery $search): View
    {
        $term = $request->queryText();

        return view('search.index', ['term' => $term, 'posts' => $search->search($term)]);
    }
}
