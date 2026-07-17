@extends('layouts.frontend')

@section('title', $term !== '' ? 'Search results for '.$term : 'Search Daily Samvad')
@section('meta_description', 'Search published news on Daily Samvad.')
@section('robots', 'noindex, follow')
@section('canonical', route('search'))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-black text-slate-950 dark:text-white">Search</h1>
        <x-frontend.search-form :term="$term" class="mt-6" />

        <div class="mt-10 grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <section aria-labelledby="search-results-heading">
                <x-frontend.section-heading id="search-results-heading">
                    {{ $term !== '' ? 'Results for “'.$term.'”' : 'Search Results' }}
                </x-frontend.section-heading>
                <div class="grid gap-6 sm:grid-cols-2">
                    @forelse ($posts as $post)
                        <x-news.medium-card :post="$post" />
                    @empty
                        <p class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-slate-500 dark:border-slate-700 dark:text-slate-400">No published news matched your search.</p>
                    @endforelse
                </div>
                <div class="mt-8">{{ $posts->links() }}</div>
            </section>
            <x-frontend.sidebar />
        </div>
    </div>
@endsection
