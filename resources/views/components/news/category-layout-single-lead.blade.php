@props(['posts', 'showMeta' => true])

@php($items = collect($posts)->values())
<div class="ds-category-single {{ $items->count() === 1 ? 'is-single' : '' }}" data-category-layout="single-lead">
    <x-news.lead-card :post="$items->first()" :show-meta="$showMeta" />
    @if ($items->count() > 1)
        <div class="ds-category-single__list">
            @foreach ($items->slice(1) as $post)<x-news.compact-card :post="$post" :show-meta="$showMeta" />@endforeach
        </div>
    @endif
</div>
