@props(['post', 'showCategory' => true])

<div {{ $attributes->class('flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-medium text-slate-500 dark:text-slate-400') }}>
    @if ($showCategory && $post->relationLoaded('primaryCategory') && $post->primaryCategory->isNotEmpty())
        <span class="text-amber-700 dark:text-amber-400">{{ $post->primaryCategory->first()->name }}</span>
    @endif
    @if ($post->published_at)
        <time datetime="{{ $post->published_at->toIso8601String() }}">{{ $post->published_at->diffForHumans() }}</time>
    @endif
</div>
