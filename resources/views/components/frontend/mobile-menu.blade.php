@props(['items' => collect()])

@php
    $currentSlug = (string) request()->route('slug');
@endphp

<nav id="ds-mobile-navigation" class="ds-mobile-nav" data-mobile-menu aria-label="Mobile navigation" hidden>
    <div class="ds-container">
        <ul class="ds-mobile-nav__list">
            <li><a class="ds-mobile-nav__link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>होम</a></li>
            @foreach ($items as $item)
                @php
                    $children = collect($item['children'] ?? []);
                    $isActive = $item['slug'] === $currentSlug || $children->contains(fn ($child) => $child['slug'] === $currentSlug);
                @endphp
                <li class="ds-mobile-nav__item">
                    @if ($children->isNotEmpty())
                        <div class="ds-mobile-nav__row">
                            <span class="ds-mobile-nav__label {{ $isActive ? 'is-active' : '' }}">{{ $item['label'] }}</span>
                            <button class="ds-mobile-nav__submenu-trigger" type="button" data-submenu-trigger aria-controls="ds-mobile-states" aria-expanded="{{ $isActive ? 'true' : 'false' }}">
                                <span class="ds-visually-hidden">Toggle राज्य submenu</span>
                                <svg class="ds-nav-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
                            </button>
                        </div>
                        <ul id="ds-mobile-states" class="ds-mobile-nav__submenu" @if(! $isActive) hidden @endif>
                            @foreach ($children as $child)
                                <li><a class="ds-mobile-nav__sublink {{ $child['slug'] === $currentSlug ? 'is-active' : '' }}" href="{{ $child['url'] }}" @if($child['slug'] === $currentSlug) aria-current="page" @endif>{{ $child['label'] }}</a></li>
                            @endforeach
                        </ul>
                    @else
                        <a class="ds-mobile-nav__link {{ $isActive ? 'is-active' : '' }}" href="{{ $item['url'] }}" @if($isActive) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</nav>
