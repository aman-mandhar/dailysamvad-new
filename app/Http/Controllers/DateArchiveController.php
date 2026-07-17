<?php

namespace App\Http\Controllers;

use App\Queries\ArchivePageQuery;
use Illuminate\Contracts\View\View;

class DateArchiveController extends Controller
{
    public function year(int $year, ArchivePageQuery $archives): View
    {
        return view('archives.index', ['archive' => $archives->forDate($year)]);
    }

    public function month(int $year, int $month, ArchivePageQuery $archives): View
    {
        return view('archives.index', ['archive' => $archives->forDate($year, $month)]);
    }

    public function day(int $year, int $month, int $day, ArchivePageQuery $archives): View
    {
        return view('archives.index', ['archive' => $archives->forDate($year, $month, $day)]);
    }
}
