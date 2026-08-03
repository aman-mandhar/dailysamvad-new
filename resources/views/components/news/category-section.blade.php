@props(['section'])

@php
    $posts = collect($section['posts'] ?? []);
    $headingId = 'homepage-section-'.preg_replace('/[^a-z0-9-]+/i', '-', $section['key']);
@endphp

@if ($posts->isNotEmpty())
    <section class="ds-category-section" data-category-section="{{ $section['key'] }}" data-section-source="{{ $section['source'] }}" aria-labelledby="{{ $headingId }}">
        <header class="ds-category-section__header">
            <h2 class="ds-category-section__title" id="{{ $headingId }}">{{ $section['title'] }}</h2>
            <span class="ds-category-section__rule" aria-hidden="true"></span>
            @if (($section['view_all'] ?? false) && $section['url'])
                <a class="ds-category-section__more" href="{{ $section['url'] }}">सभी देखें <span aria-hidden="true">→</span><span class="ds-visually-hidden">— {{ $section['title'] }}</span></a>
            @endif
        </header>
        <div class="ds-category-section__body">
            <x-dynamic-component :component="'news.category-layout-'.$section['layout']" :posts="$posts" :show-meta="$section['show_meta'] ?? true" />
        </div>
    </section>
@endif
