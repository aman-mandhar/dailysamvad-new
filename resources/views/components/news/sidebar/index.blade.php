@props(['widgets' => collect(), 'sticky' => false, 'label' => 'News sidebar', 'context' => null])

@if (collect($widgets)->isNotEmpty())
    <aside class="ds-sidebar {{ $sticky ? 'ds-sidebar--sticky' : '' }}" @if($context) data-sidebar-context="{{ $context }}" @else data-homepage-sidebar @endif aria-label="{{ $label }}">
        <div class="ds-sidebar__inner">
            @foreach ($widgets as $widget)
                @switch($widget->type)
                    @case('advertisement')<x-news.advertisement-slot :advertisement="$widget->advertisement" />@break
                    @case('latest-news')<x-news.sidebar.latest-news :widget="$widget" />@break
                    @case('popular-news')<x-news.sidebar.popular-news :widget="$widget" />@break
                    @case('categories')<x-news.sidebar.category-list :widget="$widget" />@break
                    @case('social-follow')<x-news.sidebar.social-follow :widget="$widget" />@break
                @endswitch
            @endforeach
        </div>
    </aside>
@endif
