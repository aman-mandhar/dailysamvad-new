<?php

namespace App\Http\Controllers;

use App\Queries\HomepageQuery;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(HomepageQuery $homepage): View
    {
        return view('home', $homepage->get());
    }
}
