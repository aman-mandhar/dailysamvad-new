@extends('layouts.frontend')

@section('title', $article->seoTitle)
@section('meta_description', $article->seoDescription)
@section('robots', $article->robots)
@section('canonical', $article->canonicalUrl)
@section('og_type', 'article')
@section('og_title', $article->seoTitle)
@section('og_description', $article->seoDescription)
@section('og_url', $article->canonicalUrl)
@section('twitter_title', $article->seoTitle)
@section('twitter_description', $article->seoDescription)
@section('article_published_time', $article->post->published_at->toIso8601String())

@if ($article->post->featured_image_url)
    @section('og_image', $article->post->featured_image_url)
    @section('twitter_image', $article->post->featured_image_url)
@endif

@push('json-ld')
    <script type="application/ld+json">{!! json_encode($article->structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <script type="application/ld+json">{!! json_encode($article->breadcrumbStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <div class="ds-container ds-article-page">
        <x-news.breadcrumbs :items="$article->breadcrumbs" />

        <div class="ds-article-layout">
            <article class="ds-article" aria-labelledby="article-headline">
                <x-news.article.header :article="$article" />
                <x-news.advertisement-slot :advertisement="$article->topAdvertisement" />
                <x-news.article.featured-image :article="$article" />
                <x-news.article.share :share="$article->shareUrls" />
                <x-news.article.content :blocks="$article->contentBlocks" />
                <x-news.article.tags :tags="$article->post->tags" />
                <x-news.advertisement-slot :advertisement="$article->bottomAdvertisement" />
                <x-news.article.previous-next :previous="$article->previousPost" :next="$article->nextPost" />
                <x-news.article.related-news :posts="$article->relatedPosts" />
            </article>

            <x-news.sidebar.index :widgets="$article->sidebarWidgets" :sticky="$article->sidebarSticky" label="Article sidebar" context="article" />
        </div>
    </div>
@endsection
