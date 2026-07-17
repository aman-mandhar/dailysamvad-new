@props(['code', 'title', 'message'])

<div class="mx-auto max-w-4xl px-4 py-16 text-center sm:px-6 lg:px-8">
    <p class="text-sm font-black uppercase tracking-[0.3em] text-amber-600">Error {{ $code }}</p>
    <h1 class="mt-3 text-4xl font-black text-slate-950 sm:text-5xl dark:text-white">{{ $title }}</h1>
    <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-600 dark:text-slate-300">{{ $message }}</p>
    <a href="{{ route('home') }}" class="mt-8 inline-flex rounded-lg bg-amber-500 px-6 py-3 font-bold text-slate-950">Return to homepage</a>
    {{ $slot }}
</div>
