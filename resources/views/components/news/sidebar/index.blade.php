@props(['widgets' => collect(), 'sticky' => false, 'label' => 'News sidebar', 'context' => null])

@php($showVideoPlayer = $context === 'article')

@if (collect($widgets)->isNotEmpty() || $showVideoPlayer)
    <aside class="ds-sidebar {{ $sticky ? 'ds-sidebar--sticky' : '' }}" @if($sticky) data-sticky-column @endif @if($context === 'homepage' || $context === null) data-homepage-sidebar @else data-sidebar-context="{{ $context }}" @endif aria-label="{{ $label }}">
        <div class="ds-sidebar__inner">
            @foreach ($widgets as $widget)
                @switch($widget->type)
                    @case('advertisement')<x-advertisement.slot :advertisement="$widget->advertisement" />@break
                    @case('latest-news')<x-news.sidebar.latest-news :widget="$widget" />@break
                    @case('popular-news')<x-news.sidebar.popular-news :widget="$widget" />@break
                    @case('social-follow')<x-news.sidebar.social-follow :widget="$widget" />@break
                @endswitch

                @if ($loop->first && $showVideoPlayer)
                    <x-youtube-playlist-player :placement="$context ?: 'homepage'" />
                @endif
            @endforeach

            @if (collect($widgets)->isEmpty() && $showVideoPlayer)
                <x-youtube-playlist-player :placement="$context ?: 'homepage'" />
            @endif

            @if (in_array($context, ['homepage', 'article'], true) && config('services.mgid.sidebar_widget_id'))
                <x-news.sidebar.mgid-widget :widget-id="config('services.mgid.sidebar_widget_id')" />
            @endif
        </div>
    </aside>
@endif
