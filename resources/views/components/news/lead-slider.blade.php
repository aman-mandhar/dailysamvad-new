@props(['posts' => collect()])

@php
    $slides = collect($posts)->values();
    $hasMultipleSlides = $slides->count() > 1;
@endphp

@if ($slides->isNotEmpty())
    <section class="ds-lead-slider {{ $hasMultipleSlides ? 'has-multiple' : 'is-static' }}" data-lead-slider role="region" aria-roledescription="carousel" aria-label="Top stories">
        <div class="ds-lead-slider__slides">
            @foreach ($slides as $index => $post)
                @php
                    $category = $post->relationLoaded('primaryCategory') ? $post->primaryCategory->first() : null;
                    $isActive = $index === 0;
                @endphp
                <article class="ds-lead-slide {{ $isActive ? 'is-active' : '' }}" data-lead-slide data-hero-post="{{ $post->getKey() }}" aria-hidden="{{ $isActive ? 'false' : 'true' }}" @if(! $isActive) hidden @endif>
                    <x-news.image :post="$post" :loading="$isActive ? 'eager' : 'lazy'" class="ds-lead-slide__media" />
                    <div class="ds-lead-slide__overlay" aria-hidden="true"></div>
                    <div class="ds-lead-slide__content">
                        @if ($category)
                            <a class="ds-lead-slide__badge" href="{{ route('categories.show', $category->slug) }}" @if(! $isActive) tabindex="-1" @endif>{{ $category->name }}</a>
                        @endif
                        <h2 class="ds-lead-slide__title">
                            <a href="{{ route('news.show', $post->slug) }}" @if(! $isActive) tabindex="-1" @endif>{{ $post->title }}</a>
                        </h2>
                        @if ($post->author || $post->published_at)
                            <div class="ds-lead-slide__meta">
                                @if ($post->author)<span>{{ $post->author->name }}</span>@endif
                                @if ($post->published_at)<time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->format('F j, Y') }}</time>@endif
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        @if ($hasMultipleSlides)
            <button class="ds-lead-slider__arrow ds-lead-slider__arrow--previous" type="button" data-slider-previous aria-label="Previous top story">‹</button>
            <button class="ds-lead-slider__arrow ds-lead-slider__arrow--next" type="button" data-slider-next aria-label="Next top story">›</button>
            <div class="ds-lead-slider__footer">
                <div class="ds-lead-slider__dots" aria-label="Choose top story">
                    @foreach ($slides as $index => $post)
                        <button type="button" data-slider-dot="{{ $index }}" aria-label="Show story {{ $index + 1 }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}"></button>
                    @endforeach
                </div>
                <button class="ds-lead-slider__pause" type="button" data-slider-toggle aria-pressed="false"><span data-slider-control-label>Pause top stories</span></button>
            </div>
            <p class="ds-visually-hidden" data-slider-status aria-live="polite">Story 1 of {{ $slides->count() }}</p>
        @endif
    </section>
@endif
