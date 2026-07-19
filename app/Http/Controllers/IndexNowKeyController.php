<?php

namespace App\Http\Controllers;

use App\SEO\Sitemap\IndexNowService;
use Illuminate\Http\Response;

class IndexNowKeyController extends Controller
{
    public function __invoke(string $key, IndexNowService $indexNow): Response
    {
        abort_unless($indexNow->enabled() && hash_equals((string) $indexNow->validKey(), $key), 404);

        return response($key, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
