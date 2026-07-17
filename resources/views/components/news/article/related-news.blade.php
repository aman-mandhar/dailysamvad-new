@props(['posts'])

@if ($posts->isNotEmpty())
    <section class="ds-related-news" aria-labelledby="related-news-heading">
        <x-frontend.section-heading id="related-news-heading">Related News</x-frontend.section-heading>
        <div class="ds-related-news__grid">
            @foreach ($posts as $post)
                <x-news.medium-card :post="$post" data-related-post="{{ $post->getKey() }}" />
            @endforeach
        </div>
    </section>
@endif
