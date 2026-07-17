@props(['widget'])

<section class="ds-sidebar-widget" data-sidebar-widget="{{ $widget->key }}" aria-labelledby="sidebar-{{ $widget->key }}-heading">
    <h2 id="sidebar-{{ $widget->key }}-heading" class="ds-sidebar-widget__title">{{ $widget->title }}</h2>
    <nav class="ds-sidebar-social" aria-label="Social media links">
        @foreach ($widget->items as $link)
            <a class="ds-sidebar-social__link ds-sidebar-social__link--{{ $link['network'] }}" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">{{ mb_strtoupper(mb_substr($link['label'], 0, 1)) }}</span>{{ $link['label'] }}</a>
        @endforeach
    </nav>
</section>
