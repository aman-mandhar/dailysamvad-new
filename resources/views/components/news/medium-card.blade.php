@props(['post'])

<article {{ $attributes->class('h-full overflow-hidden border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900') }}>
    <a class="group block h-full text-inherit no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700"
        href="{{ $post->publicUrl() }}" aria-label="{{ $post->title }}">
        <x-news.image :post="$post" class="aspect-video w-full object-cover" />
        <div class="p-3">
            <x-news.meta :post="$post" />
            <h3 class="mt-1.5 text-base font-bold leading-snug text-slate-950 group-hover:text-red-700 dark:text-white dark:group-hover:text-red-400">{{ $post->title }}</h3>
            @if (filled($post->excerpt))
                <p class="mt-2 line-clamp-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $post->excerpt }}</p>
            @endif
        </div>
    </a>
</article>
