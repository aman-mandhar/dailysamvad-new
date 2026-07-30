@props(['items' => collect()])

@php
    $breakingItems = collect($items);
    $shouldAnimate = $breakingItems->count() > 1;
@endphp

@if ($breakingItems->isNotEmpty())
    <section class="ds-breaking {{ $shouldAnimate ? 'is-animated' : 'is-static' }}" data-ticker aria-labelledby="breaking-news-heading">
            <h2 id="breaking-news-heading" class="sr-only">Breaking News</h2>

            <div class="ds-breaking__viewport" data-ticker-viewport tabindex="0">
                <div class="ds-breaking__track" data-ticker-track>
                    @foreach ($breakingItems as $post)
                        <article class="ds-breaking__item">
                            @if ($post->featured_image_url)
                                <img class="ds-breaking__thumb" src="{{ $post->featured_image_url }}" alt="" width="40" height="40" loading="lazy">
                            @endif
                            <a class="ds-breaking__title" href="{{ $post->publicUrl() }}">{{ $post->title }}</a>
                        </article>
                    @endforeach
                </div>
                @if ($shouldAnimate)
                    <div class="ds-breaking__track" data-ticker-clone aria-hidden="true">
                        @foreach ($breakingItems as $post)
                            <article class="ds-breaking__item">
                                @if ($post->featured_image_url)
                                    <img class="ds-breaking__thumb" src="{{ $post->featured_image_url }}" alt="" width="40" height="40" loading="lazy">
                                @endif
                                <a class="ds-breaking__title" href="{{ $post->publicUrl() }}" tabindex="-1">{{ $post->title }}</a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($shouldAnimate)
                <button class="ds-breaking__control sr-only focus:not-sr-only focus:absolute focus:right-2 focus:z-10" type="button" data-ticker-toggle aria-pressed="false">
                    <span data-ticker-control-label>Pause breaking news</span>
                </button>
            @endif
        
    </section>
@endif
