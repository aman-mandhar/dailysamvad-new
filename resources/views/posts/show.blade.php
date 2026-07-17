@extends('layouts.frontend')

@php
    $primaryCategory = $post->primaryCategory->first();
    $canonicalUrl = $post->effectiveCanonicalUrl() ?? route('news.show', $post->slug);
    $imageUrl = $post->featured_image_url;
    $articleSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'NewsArticle',
        'headline' => $post->title,
        'description' => $post->effectiveMetaDescription(),
        'datePublished' => $post->published_at?->toIso8601String(),
        'dateModified' => $post->updated_at?->toIso8601String(),
        'mainEntityOfPage' => $canonicalUrl,
        'image' => $imageUrl ? [$imageUrl] : null,
        'author' => $post->author ? ['@type' => 'Person', 'name' => $post->author->name] : null,
        'publisher' => ['@type' => 'Organization', 'name' => 'Daily Samvad'],
    ];
    $articleSchema = array_filter($articleSchema, static fn ($value) => $value !== null);
@endphp

@section('title', $post->effectiveMetaTitle())
@section('meta_description', $post->effectiveMetaDescription())
@section('canonical', $canonicalUrl)
@section('og_type', 'article')
@section('og_title', $post->effectiveMetaTitle())
@section('og_description', $post->effectiveMetaDescription())
@section('og_url', $canonicalUrl)
@section('twitter_title', $post->effectiveMetaTitle())
@section('twitter_description', $post->effectiveMetaDescription())

@if ($imageUrl)
    @section('og_image', $imageUrl)
    @section('twitter_image', $imageUrl)
@endif

@push('json-ld')
    <script type="application/ld+json">{!! json_encode($articleSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <x-frontend.advertisements.top-banner />

        <x-article.breadcrumb :post="$post" :primary-category="$primaryCategory" class="mt-6" />

        <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <article class="min-w-0" aria-labelledby="article-headline">
                @if ($primaryCategory)
                    <p class="text-sm font-bold uppercase tracking-wide text-amber-700 dark:text-amber-400">{{ $primaryCategory->name }}</p>
                @endif

                <h1 id="article-headline" class="mt-2 text-3xl font-black leading-tight tracking-tight text-slate-950 sm:text-4xl lg:text-5xl dark:text-white">
                    {{ $post->title }}
                </h1>

                <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                    @if ($post->author)
                        <span>By {{ $post->author->name }}</span>
                    @endif
                    <time datetime="{{ $post->published_at->toIso8601String() }}">Published {{ $post->published_at->diffForHumans() }}</time>
                </div>

                @if (filled($post->excerpt))
                    <p class="mt-6 text-xl leading-8 text-slate-600 dark:text-slate-300">{{ $post->excerpt }}</p>
                @endif

                <x-news.image :post="$post" class="mt-8 aspect-video w-full rounded-2xl object-cover" />

                @if (filled($post->featured_image_caption))
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">{{ $post->featured_image_caption }}</p>
                @endif

                <x-article.share-buttons :url="$canonicalUrl" :title="$post->title" class="mt-6" />

                <div class="article-content mt-8">{{ $articleContent }}</div>

                @if ($post->tags->isNotEmpty())
                    <div class="mt-8 flex flex-wrap items-center gap-2" aria-label="Article tags">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">Tags:</span>
                        @foreach ($post->tags as $tag)
                            <span class="rounded-full bg-slate-200 px-3 py-1 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $tag->name }}</span>
                        @endforeach
                    </div>
                @endif

                <x-article.author-box :author="$post->author" class="mt-10" />
                <x-article.previous-next :previous="$previousPost" :next="$nextPost" class="mt-10" />
            </article>

            <x-frontend.sidebar />
        </div>

        @if ($relatedPosts->isNotEmpty())
            <section class="mt-14" aria-labelledby="related-news-heading">
                <x-frontend.section-heading id="related-news-heading">Related News</x-frontend.section-heading>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($relatedPosts as $relatedPost)
                        <x-news.medium-card :post="$relatedPost" data-related-post="{{ $relatedPost->getKey() }}" />
                    @endforeach
                </div>
            </section>
        @endif

        <x-frontend.advertisements.footer class="mt-10" />
    </div>
@endsection
