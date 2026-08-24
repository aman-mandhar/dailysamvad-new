<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title') | {{ config('organization.website_name', 'Rzana Punjab') }}</title>
        <meta name="robots" content="noindex, nofollow">
        @vite('resources/css/app.css')
    </head>
    <body class="ds-site bg-white text-slate-950">
        <main id="main-content">
            @yield('content')
        </main>
    </body>
</html>
