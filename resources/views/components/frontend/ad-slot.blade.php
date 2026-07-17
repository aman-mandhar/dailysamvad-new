@props(['position'])

<aside {{ $attributes->class('flex min-h-24 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-100 p-4 text-center text-xs font-semibold uppercase tracking-widest text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400') }} aria-label="{{ $position }} advertisement">
    @if (trim((string) $slot) !== '')
        {{ $slot }}
    @else
        <span>Advertisement &middot; {{ $position }}</span>
    @endif
</aside>
