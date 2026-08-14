<?php

namespace App\Http\Controllers;

use App\Queries\EpaperPageQuery;
use Illuminate\Contracts\View\View;

class EpaperController extends Controller
{
    public function __invoke(string $slug, EpaperPageQuery $epapers): View
    {
        return view('epaper.show', ['epaper' => $epapers->find($slug)]);
    }
}
