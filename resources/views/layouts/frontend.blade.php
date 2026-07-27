<!DOCTYPE html>
<html lang="@yield('language', str_replace('_', '-', app()->getLocale()))" class="scheme-light dark:scheme-dark">
    <head>
        @if(config('services.google_analytics.measurement_id'))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.measurement_id') }}"></script>

        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}

        gtag('js', new Date());

        gtag('config', '{{ config('services.google_analytics.measurement_id') }}');
        </script>
        @endif
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <x-seo.meta :data="app(\App\SEO\SEOManager::class)->forCurrentPage([
            'article' => $article ?? null,
            'archive' => $archive ?? null,
            'page' => $page ?? null,
            'heroPost' => $heroPost ?? (isset($heroPosts) ? $heroPosts->first() : null),
            'title' => trim($__env->yieldContent('title')),
            'description' => trim($__env->yieldContent('meta_description')),
            'robots' => trim($__env->yieldContent('robots')),
            'canonical' => trim($__env->yieldContent('canonical')) ?: null,
        ])" />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Anek+Devanagari:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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
