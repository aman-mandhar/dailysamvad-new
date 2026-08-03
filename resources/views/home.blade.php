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
                        <x-youtube-playlist-player placement="homepage-gallery" layout="grid" :limit="20" />
                    </div>
                </section>

                @if ($featuredPosts->isNotEmpty())
                    <section aria-labelledby="featured-news-heading">
                        <x-frontend.section-heading id="featured-news-heading">Featured News</x-frontend.section-heading>
                        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($featuredPosts as $post)
                                <x-news.medium-card :post="$post" />
                            @endforeach
                        </div>
                    </section>
                @endif

                

            </div>

            <x-news.sidebar.index :widgets="$sidebarWidgets" :sticky="$sidebarSticky" />
        </div>

        @if ($categorySections->isNotEmpty())
            <div class="ds-home-sections">
                @foreach ($categorySections as $section)
                    {{-- VIDEO NEWS insertion point intentionally reserved after world. --}}
                    <x-news.category-section :section="$section" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
