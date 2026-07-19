@props(['message', 'searchQuery' => null])

<section class="ds-archive-empty" aria-labelledby="archive-empty-heading">
    <h2 id="archive-empty-heading">No news found</h2>
    <p>{{ $message }}</p>
    @if (filled($searchQuery))
        <p class="ds-archive-empty__query">Searched for: <strong>{{ $searchQuery }}</strong></p>
    @endif
    <a href="{{ route('home') }}">Return to homepage</a>
</section>
