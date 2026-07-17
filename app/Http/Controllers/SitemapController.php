<?php

namespace App\Http\Controllers;

use App\Support\SitemapXml;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SitemapController extends Controller
{
    public function __invoke(SitemapXml $sitemap): StreamedResponse
    {
        return response()->stream(
            fn () => $sitemap->write(),
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }
}
