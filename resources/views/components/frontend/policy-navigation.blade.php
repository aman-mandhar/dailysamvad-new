@php
    $policies = [
        ['Copyright Policy', 'pages.copyright'],
        ['Fact-Checking Policy', 'pages.fact-checking'],
        ['Editorial Policy', 'pages.editorial'],
        ['Disclaimer', 'pages.disclaimer'],
        ['Terms and Conditions', 'pages.terms'],
        ['Privacy Policy', 'pages.privacy'],
    ];
@endphp

<nav class="ds-policy-nav" aria-label="Policy navigation">
    <div class="ds-container ds-policy-nav__scroller">
        @foreach ($policies as [$label, $route])
            <a class="ds-policy-nav__link {{ request()->routeIs($route) ? 'is-active' : '' }}" href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </div>
</nav>
