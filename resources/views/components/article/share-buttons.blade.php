@props(['url', 'title'])

@php
    $encodedUrl = rawurlencode($url);
    $encodedTitle = rawurlencode($title);
@endphp

<nav {{ $attributes }} aria-label="Share this article">
    <ul class="flex flex-wrap gap-2 text-sm font-semibold">
        <li><a href="https://www.facebook.com/sharer/sharer.php?u={{ $encodedUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-full border border-slate-300 px-3 py-2 hover:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-slate-700">Facebook</a></li>
        <li><a href="https://x.com/intent/post?url={{ $encodedUrl }}&text={{ $encodedTitle }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-full border border-slate-300 px-3 py-2 hover:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-slate-700">X</a></li>
        <li><a href="https://wa.me/?text={{ $encodedTitle }}%20{{ $encodedUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-full border border-slate-300 px-3 py-2 hover:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-slate-700">WhatsApp</a></li>
        <li><a href="https://t.me/share/url?url={{ $encodedUrl }}&text={{ $encodedTitle }}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-full border border-slate-300 px-3 py-2 hover:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-slate-700">Telegram</a></li>
        <li><button type="button" data-copy-url="{{ $url }}" class="inline-flex rounded-full border border-slate-300 px-3 py-2 hover:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-slate-700">Copy Link</button></li>
    </ul>
</nav>
