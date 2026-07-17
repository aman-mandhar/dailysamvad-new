<?php

namespace App\Http\Controllers;

use App\Queries\ArchiveQuery;
use Illuminate\Contracts\View\View;

class TagController extends Controller
{
    public function __invoke(string $slug, ArchiveQuery $archives): View
    {
        return view('archives.index', $archives->tag($slug));
    }
}
