@props(['items' => collect()])

@php
    $breakingItems = collect($items);
@endphp

@if ($breakingItems->isNotEmpty())
    <section class="bg-red-700 text-white" aria-labelledby="breaking-news-heading">
        <div class="mx-auto flex max-w-7xl items-start gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <h2 id="breaking-news-heading" class="shrink-0 text-sm font-bold uppercase tracking-wide">Breaking</h2>
            <ul class="min-w-0 space-y-1 text-sm" aria-live="polite">
                @foreach ($breakingItems as $item)
                    <li>{{ data_get($item, 'title', $item) }}</li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
