@props(['post', 'class' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 640px) 100vw, 33vw', 'fetchpriority' => null])
@php($image = $post->responsiveFeaturedImage())

<x-news.responsive-image
    :src="$image['src']"
    :srcset="$image['srcset']"
    :sizes="$sizes"
    :width="$image['width']"
    :height="$image['height']"
    :alt="$post->featured_image_alt ?: ($post->meta_title ?: $post->title)"
    :loading="$loading"
    :fetchpriority="$fetchpriority"
    :class="$class"
    {{ $attributes }}
/>
