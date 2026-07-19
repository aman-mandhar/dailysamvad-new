@props(['article'])
@php($image = $article->post->responsiveFeaturedImage())

@if ($image['src'])
    <figure class="ds-article-featured-image">
        <x-news.responsive-image :src="$image['src']" :srcset="$image['srcset']" sizes="(max-width: 1023px) 100vw, 860px" :width="$image['width']" :height="$image['height']" :alt="$article->post->featured_image_alt ?: $article->post->title" loading="eager" fetchpriority="high" :fallback="false" />
        @if (filled($article->post->featured_image_caption))
            <figcaption>{{ $article->post->featured_image_caption }}</figcaption>
        @endif
    </figure>
@endif
