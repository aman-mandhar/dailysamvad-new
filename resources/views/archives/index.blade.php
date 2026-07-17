@extends('layouts.frontend')

@php
    $seoDescription = $metaDescription ?: 'Browse published news from Daily Samvad.';
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => $metaTitle,
        'description' => $seoDescription,
        'url' => request()->fullUrl(),
    ];
@endphp

@section('title', $metaTitle)
@section('meta_description', $seoDescription)
@section('canonical', request()->integer('page', 1) > 1 ? request()->fullUrl() : $canonical)
@section('og_title', $metaTitle)
@section('og_description', $seoDescription)
@section('og_url', request()->fullUrl())
@section('twitter_title', $metaTitle)
@section('twitter_description', $seoDescription)

@push('json-ld')
    <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <x-archive.breadcrumb :title="$title" :type="$type" />

        <header class="mt-6 rounded-2xl bg-slate-950 p-6 text-white sm:p-8">
            @if ($type === 'author')
                <x-archive.author-header :author="$entity" />
            @else
                <p class="text-sm font-bold uppercase tracking-wider text-amber-400">{{ ucfirst($type) }}</p>
                <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">{{ $title }}</h1>
                @if (filled($description))
                    <p class="mt-4 max-w-3xl leading-7 text-slate-300">{{ $description }}</p>
                @endif
            @endif
        </header>

        <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <section class="min-w-0" aria-labelledby="archive-posts-heading">
                <x-frontend.section-heading id="archive-posts-heading">Published News</x-frontend.section-heading>
                <div class="grid gap-6 sm:grid-cols-2">
                    @forelse ($posts as $post)
                        <x-news.medium-card :post="$post" />
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                            No published news is available in this archive.
                        </div>
                    @endforelse
                </div>
                <div class="mt-8">{{ $posts->links() }}</div>
            </section>

            <x-frontend.sidebar />
        </div>
    </div>
@endsection
