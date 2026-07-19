@props(['previous' => null, 'next' => null])

@if ($previous || $next)
    <nav {{ $attributes->class('grid gap-4 border-y border-slate-200 py-6 sm:grid-cols-2 dark:border-slate-800') }} aria-label="Article navigation">
        <div>
            @if ($previous)
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Previous article</span>
                <a href="{{ $previous->publicUrl() }}" class="mt-1 block font-bold text-slate-950 hover:text-amber-700 dark:text-white dark:hover:text-amber-400">{{ $previous->title }}</a>
            @endif
        </div>
        <div class="sm:text-right">
            @if ($next)
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Next article</span>
                <a href="{{ $next->publicUrl() }}" class="mt-1 block font-bold text-slate-950 hover:text-amber-700 dark:text-white dark:hover:text-amber-400">{{ $next->title }}</a>
            @endif
        </div>
    </nav>
@endif
