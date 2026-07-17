@props(['previous', 'next'])

@if ($previous || $next)
    <nav class="ds-article-navigation" aria-label="Article navigation">
        @if ($previous)
            <a rel="prev" href="{{ route('news.show', $previous->slug) }}"><span>Previous article</span><strong>{{ $previous->title }}</strong></a>
        @endif
        @if ($next)
            <a rel="next" href="{{ route('news.show', $next->slug) }}"><span>Next article</span><strong>{{ $next->title }}</strong></a>
        @endif
    </nav>
@endif
