<?php

namespace App\Http\Controllers;

use App\Queries\ArchivePageQuery;
use Illuminate\Contracts\View\View;

class TagController extends Controller
{
    public function __invoke(string $slug, ArchivePageQuery $archives): View
    {
        return view('archives.index', ['archive' => $archives->forTag($slug)]);
    }
}
