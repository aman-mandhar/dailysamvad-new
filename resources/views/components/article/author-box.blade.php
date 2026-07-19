@props(['author'])

@if ($author)
    @php($avatarUrl = $author->avatar_url)
    <aside {{ $attributes->class('flex gap-4 rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900') }} aria-labelledby="author-box-heading">
        @if ($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="{{ $author->name }}" loading="lazy" class="size-16 shrink-0 rounded-full object-cover">
        @else
            <div class="flex size-16 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xl font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300" aria-hidden="true">
                {{ str($author->name)->substr(0, 1)->upper() }}
            </div>
        @endif
        <div>
            <h2 id="author-box-heading" class="font-bold text-slate-950 dark:text-white">{{ $author->name }}</h2>
            @if (filled($author->bio))
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $author->bio }}</p>
            @endif
        </div>
    </aside>
@endif
