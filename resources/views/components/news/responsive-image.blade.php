@props([
    'src',
    'alt' => '',
    'width' => null,
    'height' => null,
    'srcset' => null,
    'sizes' => null,
    'loading' => 'lazy',
    'decoding' => 'async',
    'fetchpriority' => null,
    'fallback' => true,
    'class' => '',
    'sources' => [],
])

@if ($src)
    @if ($sources)
        <picture>
            @foreach ($sources as $source)
                @if (!empty($source['srcset']))<source srcset="{{ $source['srcset'] }}" @if (!empty($source['type'])) type="{{ $source['type'] }}" @endif @if (!empty($source['sizes'])) sizes="{{ $source['sizes'] }}" @endif>@endif
            @endforeach
    @endif
    <img
        src="{{ $src }}"
        alt="{{ $alt }}"
        @if ($width) width="{{ $width }}" @endif
        @if ($height) height="{{ $height }}" @endif
        @if ($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
        loading="{{ $loading }}"
        decoding="{{ $decoding }}"
        @if ($fetchpriority) fetchpriority="{{ $fetchpriority }}" @endif
        {{ $attributes->class($class) }}
    >
    @if ($sources)</picture>@endif
@elseif ($fallback)
    <div {{ $attributes->class(['flex items-center justify-center bg-slate-200 text-sm font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400', $class]) }} role="img" aria-label="No image available">
        Rzana Punjab
    </div>
@endif
