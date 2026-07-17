<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = ['User-agent: *'];

        foreach (config('publication.robots.allow', []) as $path) {
            $lines[] = 'Allow: '.$path;
        }

        foreach (config('publication.robots.disallow', []) as $path) {
            $lines[] = 'Disallow: '.$path;
        }

        $lines[] = 'Sitemap: '.route('sitemap');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
