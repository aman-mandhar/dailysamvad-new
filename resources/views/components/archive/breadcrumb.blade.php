@props(['title', 'type'])

<nav {{ $attributes }} aria-label="Breadcrumb">
    <ol class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
        <li><a href="{{ route('home') }}" class="hover:text-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:hover:text-amber-400">Home</a></li>
        <li aria-hidden="true">/</li>
        <li>{{ ucfirst($type) }}</li>
        <li aria-hidden="true">/</li>
        <li class="text-slate-700 dark:text-slate-200" aria-current="page">{{ $title }}</li>
    </ol>
</nav>
