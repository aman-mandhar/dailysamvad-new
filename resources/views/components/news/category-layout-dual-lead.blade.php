@props(['posts', 'showMeta' => true])

@php($items = collect($posts)->values())
@if ($items->count() === 1)
    <x-news.category-layout-single-lead :posts="$items" :show-meta="$showMeta" />
@else
    <div class="ds-category-dual" data-category-layout="dual-lead">
        <div class="ds-category-dual__leads">
            @foreach ($items->take(2) as $post)<x-news.lead-card :post="$post" :show-meta="$showMeta" />@endforeach
        </div>
        @if ($items->count() > 2)
            <div class="ds-category-dual__list">
                @foreach ($items->slice(2) as $post)<x-news.compact-card :post="$post" :show-meta="$showMeta" />@endforeach
            </div>
        @endif
    </div>
@endif
