@props(['post'])

<article data-hero-post="{{ $post->getKey() }}" class="grid overflow-hidden rounded-2xl bg-slate-950 text-white shadow-sm lg:grid-cols-5">
    <x-news.image :post="$post" loading="eager" fetchpriority="high" sizes="(max-width: 1023px) 100vw, 60vw" class="h-72 w-full object-cover lg:col-span-3 lg:h-full" />
    <div class="flex flex-col justify-center p-6 sm:p-8 lg:col-span-2">
        <x-news.meta :post="$post" class="text-slate-300" />
        <h2 class="mt-3 text-2xl font-black leading-tight sm:text-3xl">{{ $post->title }}</h2>
        @if (filled($post->effectiveMetaDescription()))
            <p class="mt-4 text-sm leading-6 text-slate-300">{{ $post->effectiveMetaDescription() }}</p>
        @endif
    </div>
</article>
