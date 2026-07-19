@extends('layouts.frontend')

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
