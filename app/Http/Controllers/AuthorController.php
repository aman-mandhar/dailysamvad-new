<?php

namespace App\Http\Controllers;

use App\Queries\ArchivePageQuery;
use Illuminate\Contracts\View\View;

class AuthorController extends Controller
{
    public function __invoke(string $username, ArchivePageQuery $archives): View
    {
        return view('archives.index', ['archive' => $archives->forAuthor($username)]);
    }
}
