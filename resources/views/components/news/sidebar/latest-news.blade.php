@props(['widget'])

<section class="ds-sidebar-widget" data-sidebar-widget="{{ $widget->key }}" aria-labelledby="sidebar-{{ $widget->key }}-heading">
    <h2 id="sidebar-{{ $widget->key }}-heading" class="ds-sidebar-widget__title">{{ $widget->title }}</h2>
    <div class="ds-sidebar-widget__body">
        @foreach ($widget->items as $post)<x-news.sidebar.news-item :post="$post" :show-category="$widget->showCategory" :show-date="$widget->showDate" />@endforeach
    </div>
</section>
