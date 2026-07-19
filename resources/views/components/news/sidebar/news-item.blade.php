@props(['post', 'showCategory' => false, 'showDate' => false, 'rank' => null])

<article class="ds-sidebar-news-item">
    @if ($rank)<span class="ds-sidebar-news-item__rank" aria-label="Rank {{ $rank }}">{{ $rank }}</span>@endif
    <a class="ds-sidebar-news-item__image" href="{{ $post->publicUrl() }}" aria-label="{{ $post->title }}">
        <x-news.image :post="$post" class="ds-image-ratio-square" width="88" height="88" />
    </a>
    <div class="ds-sidebar-news-item__content">
        <h3><a href="{{ $post->publicUrl() }}">{{ $post->title }}</a></h3>
        @if ($showCategory || ($showDate && $post->published_at))
            <div class="ds-sidebar-news-item__meta">
                @if ($showCategory && $post->primaryCategory->isNotEmpty())<span>{{ $post->primaryCategory->first()->name }}</span>@endif
                @if ($showDate && $post->published_at)<time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->diffForHumans() }}</time>@endif
            </div>
        @endif
    </div>
</article>
