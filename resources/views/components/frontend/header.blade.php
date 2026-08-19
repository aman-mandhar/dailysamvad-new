@props(['mainMenu' => collect(), 'breakingNews' => collect()])

@php
    $advertisementContext = ['page_type' => match (true) {
        request()->routeIs('home') => 'home',
        request()->routeIs('news.*') => 'article',
        request()->routeIs('categories.*') => 'category',
        request()->routeIs('tags.*') => 'tag',
        request()->routeIs('authors.*') => 'author',
        request()->routeIs('search') => 'search',
        request()->routeIs('archives.*') => 'archive',
        default => null,
    }];
@endphp

<div class="ds-header__nonsticky" data-brand="DailySamvad">
    <div class="ds-header__branding ds-header__advertisement ds-container">
        <div class="ds-header__right">
            <x-advertisement.slot :position="\App\Enums\AdvertisementPosition::HeaderTop" :context="$advertisementContext" />
        </div>
    </div>
</div>

<header class="ds-header ds-header__sticky-row" data-header>
    <div class="ds-header__desktop-row ds-container">
            <x-frontend.logo />
            <div class="ds-header__desktop">
                <x-frontend.navigation :items="$mainMenu" />
            </div>
        </div>

    <div class="ds-header__mobile ds-container">
            <div class="ds-header__mobile-brand">
                <x-frontend.logo />
            </div>
            <div class="ds-header__mobile-navigation">
                <button class="ds-header-control ds-header-control--menu" type="button" data-mobile-menu-trigger aria-controls="ds-mobile-navigation" aria-expanded="false">
                    <span class="ds-visually-hidden" data-menu-label>Open main menu</span>
                    <svg class="ds-icon ds-icon--menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                    <svg class="ds-icon ds-icon--close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" /></svg>
                </button>
                <button class="ds-header-control ds-header-control--search {{ request()->routeIs('search') ? 'is-active' : '' }}" type="button" data-search-trigger aria-controls="ds-header-search" aria-expanded="false">
                    <span class="ds-visually-hidden">Open search</span>
                    <svg class="ds-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-4-4" /></svg>
                </button>
            </div>
        </div>
</header>

<x-frontend.mobile-menu :items="$mainMenu" />
<x-frontend.policy-navigation />
<x-frontend.header-search />
<div class="ds-header__mobile-ad">
        <x-advertisement.slot :position="\App\Enums\AdvertisementPosition::HeaderTop" :context="$advertisementContext" />
    </div>
