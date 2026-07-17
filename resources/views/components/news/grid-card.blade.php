@props(['post', 'showMeta' => true])

<article {{ $attributes->class('ds-news-grid-card') }}>
    <a class="ds-news-card__image ds-news-card__image--wide" href="{{ route('news.show', $post->slug) }}" aria-label="{{ $post->title }}">
        <x-news.image :post="$post" class="ds-image-ratio-news" />
    </a>
    <div class="ds-news-card__content">
        @if ($showMeta)<x-news.meta :post="$post" class="ds-news-card__meta" />@endif
        <h3 class="ds-news-card__title"><a href="{{ route('news.show', $post->slug) }}">{{ $post->title }}</a></h3>
    </div>
</article>
