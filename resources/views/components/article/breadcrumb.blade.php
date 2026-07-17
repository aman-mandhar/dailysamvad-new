@props(['post', 'primaryCategory' => null])

<nav {{ $attributes }} aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <li><a href="{{ route('home') }}" class="rounded-sm hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:hover:text-amber-400">Home</a></li>
        @if ($primaryCategory)
            <li aria-hidden="true">/</li>
            <li>{{ $primaryCategory->name }}</li>
        @endif
        <li aria-hidden="true">/</li>
        <li class="max-w-72 truncate text-slate-700 dark:text-slate-200" aria-current="page">{{ $post->title }}</li>
    </ol>
</nav>
