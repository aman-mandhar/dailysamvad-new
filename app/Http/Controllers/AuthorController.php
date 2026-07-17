<?php

namespace App\Http\Controllers;

use App\Queries\ArchiveQuery;
use Illuminate\Contracts\View\View;

class AuthorController extends Controller
{
    public function __invoke(string $username, ArchiveQuery $archives): View
    {
        return view('archives.index', $archives->author($username));
    }
}
