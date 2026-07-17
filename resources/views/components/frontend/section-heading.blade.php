@props(['id'])

<div class="mb-5 flex items-center gap-3 border-b border-slate-200 pb-3 dark:border-slate-800">
    <span class="h-6 w-1 rounded-full bg-amber-500" aria-hidden="true"></span>
    <h2 id="{{ $id }}" {{ $attributes->class('text-xl font-bold text-slate-950 dark:text-white') }}>{{ $slot }}</h2>
</div>
