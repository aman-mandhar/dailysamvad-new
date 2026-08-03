@extends('layouts.frontend')

@section('content')
    <div class="ds-container py-4 sm:py-5">
        <div class="ds-home-top ds-main-grid mt-6">
            <div class="min-w-0 space-y-8">
                @if ($heroPosts->isNotEmpty())
                    <x-news.lead-slider :posts="$heroPosts" />
                    <x-frontend.whatsapp-join />
                @elseif (app()->environment(['local', 'development']))
                    <div class="ds-home-top__empty">Published top stories will appear here.</div>
                @endif
                <x-advertisement.slot :advertisement="$homepageInlineAdvertisement" />
                <section aria-labelledby="video-news-heading">
                    <!-- display video player here-->
                    <x-frontend.section-heading id="video-news-heading">Video News</x-frontend.section-heading>
                    <div class="space-y-4">
                        <x-youtube-playlist-player placement="homepage" />
                        <x-youtube-playlist-player placement="homepage-gallery" layout="grid" :limit="12" />
                    </div>
                </section>
            </div>

            <x-news.sidebar.index :widgets="$sidebarWidgets" :sticky="$sidebarSticky" />
        </div>

        @if ($categorySections->isNotEmpty())
            <section class="ds-home-sections-shell" data-home-category-sections aria-labelledby="more-stories-heading">
                <header class="ds-home-sections-shell__header">
                    <div>
                        <p class="ds-home-sections-shell__eyebrow">Explore Daily Samvad</p>
                        <h2 id="more-stories-heading">More Stories</h2>
                    </div>
                    <p>Latest reporting, organised by topic.</p>
                </header>

                <div class="ds-home-sections">
                    @foreach ($categorySections as $section)
                        <x-news.category-section :section="$section" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
