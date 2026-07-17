@props(['post'])

<article {{ $attributes->class('overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900') }}>
    <x-news.image :post="$post" class="aspect-video w-full object-cover" />
    <div class="p-5">
        <x-news.meta :post="$post" />
        <h3 class="mt-2 text-lg font-bold leading-snug text-slate-950 dark:text-white">{{ $post->title }}</h3>
        @if (filled($post->excerpt))
            <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $post->excerpt }}</p>
        @endif
    </div>
</article>
