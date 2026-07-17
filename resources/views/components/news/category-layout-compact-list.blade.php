@props(['posts', 'showMeta' => true])

<div class="ds-category-compact" data-category-layout="compact-list">
    @foreach ($posts as $post)<x-news.compact-card :post="$post" :show-meta="$showMeta" />@endforeach
</div>
