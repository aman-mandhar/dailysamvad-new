@props(['term' => ''])

<form action="{{ route('search') }}" method="GET" {{ $attributes->class('flex max-w-2xl gap-2') }} role="search">
    <label for="site-search" class="sr-only">Search news</label>
    <input id="site-search" type="search" name="q" value="{{ $term }}" maxlength="200" placeholder="Search published news" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-4 py-3 text-slate-950 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/30 dark:border-slate-700 dark:bg-slate-900 dark:text-white">
    <button type="submit" class="rounded-lg bg-amber-500 px-5 py-3 font-bold text-slate-950 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-500">Search</button>
</form>
