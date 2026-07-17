<!DOCTYPE html>
<html lang="@yield('language', str_replace('_', '-', app()->getLocale()))" class="scheme-light dark:scheme-dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', 'Daily Samvad')</title>
        <meta name="description" content="@yield('meta_description', 'Daily Samvad multilingual news platform.')">
        <meta name="robots" content="@yield('robots', 'index, follow')">
        <link rel="canonical" href="@yield('canonical', url()->current())">

        <meta property="og:type" content="@yield('og_type', 'website')">
        <meta property="og:site_name" content="Daily Samvad">
        <meta property="og:title" content="@yield('og_title', 'Daily Samvad')">
        <meta property="og:description" content="@yield('og_description', 'Daily Samvad multilingual news platform.')">
        <meta property="og:url" content="@yield('og_url', url()->current())">
        @hasSection('og_image')
            <meta property="og:image" content="@yield('og_image')">
        @endif
        @hasSection('article_published_time')
            <meta property="article:published_time" content="@yield('article_published_time')">
        @endif

        <meta name="twitter:card" content="@yield('twitter_card', 'summary_large_image')">
        <meta name="twitter:title" content="@yield('twitter_title', 'Daily Samvad')">
        <meta name="twitter:description" content="@yield('twitter_description', 'Daily Samvad multilingual news platform.')">
        @hasSection('twitter_image')
            <meta name="twitter:image" content="@yield('twitter_image')">
        @endif

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Anek+Devanagari:wght@400;500;600;700;800&display=swap" rel="stylesheet">

        @stack('json-ld')
        @stack('head')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="ds-site">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-amber-500 focus:px-4 focus:py-2 focus:font-semibold focus:text-slate-950">
            Skip to content
        </a>

        <x-frontend.header :main-menu="$mainMenu" :breaking-news="$globalBreakingNews" />

        <div class="ds-container pt-4">
            @foreach (['success', 'error', 'warning', 'info'] as $flashType)
                @if (session()->has($flashType))
                    <x-frontend.alert :type="$flashType" :message="session($flashType)" />
                @endif
            @endforeach
        </div>

        <main id="main-content" tabindex="-1">
            @yield('content')
        </main>

        <x-frontend.footer />
        @stack('scripts')
    </body>
</html>
