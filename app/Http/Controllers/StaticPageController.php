<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class StaticPageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $page = config("static-pages.{$slug}");
        abort_unless(is_array($page), 404);

        return view('pages.show', [
            'page' => $page,
            'organization' => config('organization'),
        ]);
    }
}
