@extends('layouts.frontend')

@section('title', $page['seo_title'])
@section('meta_description', $page['seo_description'])
@section('canonical', route($page['route']))
@section('robots', $page['robots'])
@section('og_title', $page['seo_title'])
@section('og_description', $page['seo_description'])
@section('og_url', route($page['route']))
@section('twitter_title', $page['seo_title'])
@section('twitter_description', $page['seo_description'])

@section('content')
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <nav aria-label="Breadcrumb">
            <ol class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <li><a href="{{ route('home') }}" class="hover:text-amber-700 dark:hover:text-amber-400">Home</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page" class="text-slate-700 dark:text-slate-200">{{ $page['title'] }}</li>
            </ol>
        </nav>

        <article class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 sm:p-10 dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-200 pb-6 dark:border-slate-800">
                <h1 class="text-3xl font-black tracking-tight text-slate-950 sm:text-4xl dark:text-white">{{ $page['title'] }}</h1>
                @if (filled($page['subtitle']))
                    <p class="mt-3 text-lg text-slate-600 dark:text-slate-300">{{ $page['subtitle'] }}</p>
                @endif
                @if (filled($page['last_updated']))
                    <p class="mt-3 text-sm text-slate-500">Last updated: {{ $page['last_updated'] }}</p>
                @endif
            </header>

            @if ($page['slug'] === 'contact-us')
                <x-static-pages.contact-details :organization="$organization" class="mt-8" />
            @endif

            <div class="mt-8 space-y-8">
                @foreach ($page['sections'] as $section)
                    <section>
                        <h2 class="text-2xl font-bold text-slate-950 dark:text-white">{{ $section['heading'] }}</h2>
                        <div class="mt-4 space-y-4 text-base leading-7 text-slate-700 dark:text-slate-300">
                            @foreach ($section['paragraphs'] as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </article>
    </div>
@endsection
