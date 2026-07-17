@props(['post', 'showCategory' => true])

<article {{ $attributes->class('flex gap-4') }}>
    <x-news.image :post="$post" class="h-20 w-28 shrink-0 rounded-lg object-cover" />
    <div class="min-w-0">
        <x-news.meta :post="$post" :show-category="$showCategory" />
        <h3 class="mt-1 line-clamp-2 font-bold leading-snug text-slate-950 dark:text-white">{{ $post->title }}</h3>
    </div>
</article>
