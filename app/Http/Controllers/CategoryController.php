<?php

namespace App\Http\Controllers;

use App\Queries\ArchiveQuery;
use Illuminate\Contracts\View\View;

class CategoryController extends Controller
{
    public function __invoke(string $slug, ArchiveQuery $archives): View
    {
        return view('archives.index', $archives->category($slug));
    }
}
