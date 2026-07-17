@props(['items' => collect()])

@php
    $currentSlug = (string) request()->route('slug');
@endphp

<nav class="ds-primary-nav" aria-label="Main navigation">
    <div class="ds-container ds-primary-nav__inner">
        <ul class="ds-primary-nav__list">
            <li>
                <a class="ds-nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>होम</a>
            </li>
            @foreach ($items as $item)
                @php
                    $children = collect($item['children'] ?? []);
                    $isActive = $item['slug'] === $currentSlug || $children->contains(fn ($child) => $child['slug'] === $currentSlug);
                @endphp
                <x-frontend.nav-item :item="$item" :active="$isActive" />
            @endforeach
        </ul>

        <button class="ds-primary-nav__search {{ request()->routeIs('search') ? 'is-active' : '' }}" type="button" data-search-trigger aria-controls="ds-header-search" aria-expanded="false">
            <span class="ds-visually-hidden">Open search</span>
            <svg class="ds-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" /></svg>
        </button>
    </div>
</nav>
