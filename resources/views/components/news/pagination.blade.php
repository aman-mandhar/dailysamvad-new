@props(['paginator'])

@if ($paginator->hasPages())
    @php
        $window = \Illuminate\Pagination\UrlWindow::make($paginator);
        $elements = array_filter([
            $window['first'],
            is_array($window['slider']) ? '...' : null,
            $window['slider'],
            is_array($window['last']) ? '...' : null,
            $window['last'],
        ]);
    @endphp
    <nav class="ds-archive-pagination" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="ds-archive-pagination__ellipsis" aria-hidden="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span aria-current="page" aria-label="Page {{ $page }}, current page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <span aria-disabled="true">Next</span>
        @endif
    </nav>
@endif
