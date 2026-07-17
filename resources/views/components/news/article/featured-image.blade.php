@props(['article'])

@if ($article->post->featured_image_url)
    <figure class="ds-article-featured-image">
        <img src="{{ $article->post->featured_image_url }}" alt="{{ $article->post->featured_image_alt ?: $article->post->title }}" width="1200" height="675" loading="eager" fetchpriority="high">
        @if (filled($article->post->featured_image_caption))
            <figcaption>{{ $article->post->featured_image_caption }}</figcaption>
        @endif
    </figure>
@endif
