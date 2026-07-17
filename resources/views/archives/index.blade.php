@extends('layouts.frontend')

@section('title', $archive->seoTitle)
@section('meta_description', $archive->seoDescription)
@section('robots', $archive->robots)
@section('canonical', $archive->canonicalUrl)
@section('og_type', 'website')
@section('og_title', $archive->seoTitle)
@section('og_description', $archive->seoDescription)
@section('og_url', $archive->canonicalUrl)
@section('twitter_title', $archive->seoTitle)
@section('twitter_description', $archive->seoDescription)

@push('json-ld')
    <script type="application/ld+json">{!! json_encode($archive->structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <script type="application/ld+json">{!! json_encode($archive->breadcrumbStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <div class="ds-container ds-archive-page">
        <x-news.breadcrumbs :items="$archive->breadcrumbs" />

        <div class="ds-archive-layout">
            <section class="ds-archive-main" aria-labelledby="archive-heading">
                <x-news.archive.header :archive="$archive" />
                <x-news.advertisement-slot :advertisement="$archive->topAdvertisement" />

                @if ($archive->contextType === 'search')
                    <x-frontend.search-form :term="$archive->searchQuery" class="ds-archive-search-form" />
                @endif

                <x-news.archive.results :archive="$archive" />
                <x-news.advertisement-slot :advertisement="$archive->inlineAdvertisement" />
                <x-news.pagination :paginator="$archive->posts" />
            </section>

            <x-news.sidebar.index :widgets="$archive->sidebarWidgets" :sticky="$archive->sidebarSticky" :label="$archive->label.' sidebar'" :context="$archive->sidebarContext" />
        </div>
    </div>
@endsection
