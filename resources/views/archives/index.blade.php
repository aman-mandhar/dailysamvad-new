@extends('layouts.frontend')

@section('content')
    <div class="ds-container ds-archive-page">
        <x-news.breadcrumbs :items="$archive->breadcrumbs" />

        <div class="ds-archive-layout">
            <section class="ds-archive-main" aria-labelledby="archive-heading">
                <x-news.archive.header :archive="$archive" />
                <x-advertisement.slot :advertisement="$archive->topAdvertisement" />

                @if ($archive->contextType === 'search')
                    <x-frontend.search-form :term="$archive->searchQuery" class="ds-archive-search-form" />
                @endif

                <x-news.archive.results :archive="$archive" />
                <x-advertisement.slot :advertisement="$archive->inlineAdvertisement" />
                <x-news.pagination :paginator="$archive->posts" />
            </section>

            <x-news.sidebar.index :widgets="$archive->sidebarWidgets" :sticky="$archive->sidebarSticky" :label="$archive->label.' sidebar'" :context="$archive->sidebarContext" />
        </div>
    </div>
@endsection
