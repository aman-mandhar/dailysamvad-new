@props(['message'])

<section class="ds-archive-empty" aria-labelledby="archive-empty-heading">
    <h2 id="archive-empty-heading">No news found</h2>
    <p>{{ $message }}</p>
    <a href="{{ route('home') }}">Return to homepage</a>
</section>
