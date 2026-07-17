@props(['posts', 'showMeta' => true])

<div class="ds-category-grid" data-category-layout="grid">
    @foreach ($posts as $post)<x-news.grid-card :post="$post" :show-meta="$showMeta" />@endforeach
</div>
