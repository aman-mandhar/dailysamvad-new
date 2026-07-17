@props(['items' => collect()])

@php
    $breakingItems = collect($items);
    $shouldAnimate = $breakingItems->count() > 1;
@endphp

@if ($breakingItems->isNotEmpty())
    <section class="ds-breaking {{ $shouldAnimate ? 'is-animated' : 'is-static' }}" data-ticker aria-labelledby="breaking-news-heading">
        <div class="ds-container ds-breaking__inner">
            <h2 id="breaking-news-heading" class="ds-breaking__label">
                <span class="ds-breaking__indicator" aria-hidden="true"></span>
                <svg class="ds-breaking__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5h16v14H4zM7 8h5v4H7zM14 8h3M14 11h3M7 15h10" /></svg>
                <span class="ds-breaking__label-long">BREAKING NEWS</span>
                <span class="ds-breaking__label-short">BREAKING</span>
            </h2>

            <div class="ds-breaking__viewport" data-ticker-viewport tabindex="0">
                <div class="ds-breaking__track" data-ticker-track>
                    @foreach ($breakingItems as $post)
                        <article class="ds-breaking__item">
                            @if ($post->featured_image_url)
                                <img class="ds-breaking__thumb" src="{{ $post->featured_image_url }}" alt="" width="40" height="40" loading="lazy">
                            @endif
                            <a class="ds-breaking__title" href="{{ route('news.show', $post->slug) }}">{{ $post->title }}</a>
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
                                <a class="ds-breaking__title" href="{{ route('news.show', $post->slug) }}" tabindex="-1">{{ $post->title }}</a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($shouldAnimate)
                <button class="ds-breaking__control" type="button" data-ticker-toggle aria-pressed="false">
                    <span data-ticker-control-label>Pause breaking news</span>
                </button>
            @endif
        </div>
    </section>
@endif
