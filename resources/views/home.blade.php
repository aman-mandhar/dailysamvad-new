@extends('layouts.frontend')

@section('title', 'Daily Samvad - Latest Hindi, Punjabi and English News')
@section('meta_description', 'Read the latest breaking, featured and regional news from Daily Samvad in Hindi, Punjabi and English.')
@section('canonical', route('home'))
@section('og_title', 'Daily Samvad - Latest News')
@section('og_description', 'Breaking news and trusted reporting in Hindi, Punjabi and English.')
@section('og_url', route('home'))
@section('twitter_title', 'Daily Samvad - Latest News')
@section('twitter_description', 'Breaking news and trusted reporting in Hindi, Punjabi and English.')

@if ($heroPosts->first()?->featured_image_url)
    @section('og_image', $heroPosts->first()->featured_image_url)
    @section('twitter_image', $heroPosts->first()->featured_image_url)
@endif

@section('content')
    <div class="ds-container py-4 sm:py-5">
        <x-news.advertisement-slot :advertisement="$homepageTopAdvertisement" />

        <div class="ds-home-top ds-main-grid mt-6">
            <div class="min-w-0 space-y-8">
                @if ($heroPosts->isNotEmpty())
                    <x-news.lead-slider :posts="$heroPosts" />
                @elseif (app()->environment(['local', 'development']))
                    <div class="ds-home-top__empty">Published top stories will appear here.</div>
                @endif

                <section aria-labelledby="latest-news-heading">
                    <x-frontend.section-heading id="latest-news-heading">Latest News</x-frontend.section-heading>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @forelse ($latestPosts as $post)
                            <x-news.medium-card :post="$post" />
                        @empty
                            <p class="text-slate-500 dark:text-slate-400">No published news is available yet.</p>
                        @endforelse
                    </div>
                    <div class="mt-6">{{ $latestPosts->links() }}</div>
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

                <x-news.advertisement-slot :advertisement="$homepageInlineAdvertisement" />

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
