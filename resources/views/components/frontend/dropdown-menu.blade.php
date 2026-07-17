@props(['items'])

@php
    $currentSlug = (string) request()->route('slug');
@endphp

<ul class="ds-nav-dropdown" aria-label="राज्य categories">
    @foreach ($items as $item)
        <li>
            <a class="ds-nav-dropdown__link {{ $item['slug'] === $currentSlug ? 'is-active' : '' }}" href="{{ $item['url'] }}" @if($item['slug'] === $currentSlug) aria-current="page" @endif>
                {{ $item['label'] }}
            </a>
        </li>
    @endforeach
</ul>
