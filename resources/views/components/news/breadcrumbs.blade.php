@props(['items'])

<nav class="ds-breadcrumbs" aria-label="Breadcrumb">
    <ol>
        @foreach ($items as $item)
            <li>
                @if (! $item['current'] && $item['url'])
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span @if($item['current']) aria-current="page" @endif>{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
