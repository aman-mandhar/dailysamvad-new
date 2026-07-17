@props(['item', 'active' => false])

@php
    $children = collect($item['children'] ?? []);
@endphp

<li class="ds-nav-item {{ $children->isNotEmpty() ? 'has-dropdown' : '' }} {{ $active ? 'is-current' : '' }}">
    @if ($children->isNotEmpty())
        <button class="ds-nav-link ds-nav-dropdown-trigger {{ $active ? 'is-active' : '' }}" type="button" aria-expanded="false" aria-haspopup="true">
            <span>{{ $item['label'] }}</span>
            <svg class="ds-nav-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" /></svg>
        </button>
        <x-frontend.dropdown-menu :items="$children" />
    @else
        <a class="ds-nav-link {{ $active ? 'is-active' : '' }}" href="{{ $item['url'] }}" @if($active) aria-current="page" @endif>{{ $item['label'] }}</a>
    @endif
</li>
