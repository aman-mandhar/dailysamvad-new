@props(['advertisement'])

@if ($advertisement?->enabled)
    <aside class="ds-ad-slot ds-ad-slot--{{ $advertisement->type }}" data-ad-slot="{{ $advertisement->slot }}" aria-label="{{ $advertisement->label }}" style="--ds-ad-width: {{ $advertisement->width }}; --ds-ad-height: {{ $advertisement->height }}">
        @if ($advertisement->type === 'html')
            <div class="ds-ad-slot__html">{!! $advertisement->html !!}</div>
        @elseif ($advertisement->type === 'image')
            @if ($advertisement->destinationUrl)
                <a href="{{ $advertisement->destinationUrl }}" @if($advertisement->openInNewTab) target="_blank" @endif rel="{{ $advertisement->rel }}">
                    <img src="{{ $advertisement->imageUrl }}" alt="{{ $advertisement->altText }}" width="{{ $advertisement->width }}" height="{{ $advertisement->height }}" loading="lazy">
                </a>
            @else
                <img src="{{ $advertisement->imageUrl }}" alt="{{ $advertisement->altText }}" width="{{ $advertisement->width }}" height="{{ $advertisement->height }}" loading="lazy">
            @endif
        @else
            <span>ADVERTISEMENT</span>
            <small>{{ $advertisement->width }} × {{ $advertisement->height }}</small>
        @endif
    </aside>
@endif
