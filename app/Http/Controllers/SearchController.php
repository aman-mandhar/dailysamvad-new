<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Queries\ArchivePageQuery;
use Illuminate\Contracts\View\View;

class SearchController extends Controller
{
    public function __invoke(SearchRequest $request, ArchivePageQuery $archives): View
    {
        $term = $request->queryText();

        return view('archives.index', ['archive' => $archives->forSearch($term)]);
    }
}
