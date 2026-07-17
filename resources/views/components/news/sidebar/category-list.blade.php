@props(['widget'])

<section class="ds-sidebar-widget" data-sidebar-widget="{{ $widget->key }}" aria-labelledby="sidebar-{{ $widget->key }}-heading">
    <h2 id="sidebar-{{ $widget->key }}-heading" class="ds-sidebar-widget__title">{{ $widget->title }}</h2>
    <ul class="ds-sidebar-categories">
        @foreach ($widget->items as $category)
            <li><a href="{{ route('categories.show', $category->slug) }}"><span>{{ $category->name }}</span>@if($widget->showCount)<span class="ds-sidebar-categories__count" aria-label="{{ $category->published_posts_count }} published posts">{{ $category->published_posts_count }}</span>@endif</a></li>
        @endforeach
    </ul>
</section>
