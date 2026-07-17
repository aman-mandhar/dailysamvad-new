@props(['author'])

@php
    $avatarUrl = filled($author->avatar_path)
        ? (str_starts_with($author->avatar_path, 'http') ? $author->avatar_path : Storage::disk('public')->url($author->avatar_path))
        : null;
    $socials = array_filter([
        'Facebook' => $author->facebook_url,
        'X' => $author->x_url,
        'Instagram' => $author->instagram_url,
        'YouTube' => $author->youtube_url,
    ]);
@endphp

<div class="flex flex-col gap-5 sm:flex-row sm:items-center">
    @if ($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="{{ $author->name }}" loading="lazy" class="size-24 rounded-full object-cover">
    @else
        <div class="flex size-24 shrink-0 items-center justify-center rounded-full bg-amber-100 text-3xl font-black text-amber-800" aria-hidden="true">
            {{ str($author->name)->substr(0, 1)->upper() }}
        </div>
    @endif
    <div>
        <p class="text-sm font-bold uppercase tracking-wider text-amber-400">Author</p>
        <h1 class="mt-1 text-3xl font-black tracking-tight sm:text-4xl">{{ $author->name }}</h1>
        @if (filled($author->bio))
            <p class="mt-3 max-w-3xl leading-7 text-slate-300">{{ $author->bio }}</p>
        @endif
        @if ($socials !== [])
            <nav class="mt-4" aria-label="Author social links">
                <ul class="flex flex-wrap gap-3 text-sm">
                    @foreach ($socials as $label => $url)
                        <li><a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-amber-400 hover:text-amber-300">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>
        @else
            <p class="mt-4 text-sm text-slate-400">Social links will appear here.</p>
        @endif
    </div>
</div>
