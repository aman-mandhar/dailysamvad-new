@props(['post'])

<article class="ds-archive-card" data-archive-post="{{ $post->getKey() }}">
    <a class="ds-archive-card__image" href="{{ $post->publicUrl() }}" aria-label="{{ $post->title }}">
        <x-news.image :post="$post" width="300" height="169" />
    </a>
    <div class="ds-archive-card__content">
        <x-news.meta :post="$post" />
        <h2><a href="{{ $post->publicUrl() }}">{{ $post->title }}</a></h2>
        @if (filled($post->excerpt))
            <p>{{ $post->excerpt }}</p>
        @endif
    </div>
</article>
