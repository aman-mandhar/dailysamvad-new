@props(['tags'])

@if ($tags->isNotEmpty())
    <section class="ds-article-tags" aria-label="Article tags">
        <span>Tags:</span>
        <ul>
            @foreach ($tags as $tag)
                <li><a href="{{ route('tags.show', $tag->slug) }}">{{ $tag->name }}</a></li>
            @endforeach
        </ul>
    </section>
@endif
