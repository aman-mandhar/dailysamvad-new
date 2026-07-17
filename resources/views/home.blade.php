@extends('layouts.frontend')

@section('title', 'Daily Samvad - Latest Hindi, Punjabi and English News')
@section('meta_description', 'Read the latest breaking, featured and regional news from Daily Samvad in Hindi, Punjabi and English.')
@section('canonical', route('home'))
@section('og_title', 'Daily Samvad - Latest News')
@section('og_description', 'Breaking news and trusted reporting in Hindi, Punjabi and English.')
@section('og_url', route('home'))
@section('twitter_title', 'Daily Samvad - Latest News')
@section('twitter_description', 'Breaking news and trusted reporting in Hindi, Punjabi and English.')

@if ($heroPost?->featured_image_url)
    @section('og_image', $heroPost->featured_image_url)
    @section('twitter_image', $heroPost->featured_image_url)
@endif

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <x-frontend.advertisements.top-banner />

        <section class="mt-8" aria-labelledby="hero-news-heading">
            <div class="mb-4 flex items-center justify-between">
                <h1 id="hero-news-heading" class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">Top Story</h1>
            </div>

            @if ($heroPost)
                <x-news.hero-card :post="$heroPost" />
            @else
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                    Published stories will appear here.
                </div>
            @endif
        </section>

        <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0 space-y-12">
                <section aria-labelledby="latest-news-heading">
                    <x-frontend.section-heading id="latest-news-heading">Latest News</x-frontend.section-heading>
                    <div class="grid gap-6 sm:grid-cols-2">
                        @forelse ($latestPosts as $post)
                            <x-news.medium-card :post="$post" />
                        @empty
                            <p class="text-slate-500 dark:text-slate-400">No published news is available yet.</p>
                        @endforelse
                    </div>
                    <div class="mt-8">{{ $latestPosts->links() }}</div>
                </section>

                @if ($featuredPosts->isNotEmpty())
                    <section aria-labelledby="featured-news-heading">
                        <x-frontend.section-heading id="featured-news-heading">Featured News</x-frontend.section-heading>
                        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($featuredPosts as $post)
                                <x-news.medium-card :post="$post" />
                            @endforeach
                        </div>
                    </section>
                @endif

                <x-frontend.advertisements.in-content />

                @if ($categoryBlocks->isNotEmpty())
                    <section aria-labelledby="category-news-heading">
                        <x-frontend.section-heading id="category-news-heading">News by Category</x-frontend.section-heading>
                        <div class="grid gap-8 xl:grid-cols-2">
                            @foreach ($categoryBlocks as $category)
                                <x-news.category-card :category="$category" />
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <x-frontend.sidebar />
        </div>
    </div>
@endsection
