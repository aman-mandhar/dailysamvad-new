@props(['position' => null, 'context' => null, 'advertisement' => null])

@php
    $ad = $advertisement ?? app(\App\Services\Advertisements\AdvertisementResolver::class)->resolve($position, $context);
    $record = $ad?->advertisementId ? \App\Models\Advertisement::find($ad->advertisementId) : null;
    $canEdit = $record && auth()->check() && auth()->user()->can('updateFromFrontend', $record);
@endphp

@if ($ad?->enabled)
    <aside class="ds-ad-slot ds-ad-slot--{{ $ad->type }}" data-ad-slot="{{ $ad->slot }}" @if($ad->advertisementUuid) data-ad-impression="{{ $ad->impressionUrl }}" @endif aria-label="{{ $ad->label }}" style="--ds-ad-width: {{ $ad->width }}; --ds-ad-height: {{ $ad->height }}">
        @if (in_array($ad->type, ['html', 'provider_code'], true))
            <div class="ds-ad-slot__html">{!! $ad->html !!}</div>
        @elseif ($ad->type === 'image')
            @php($destination = $ad->clickUrl ?: $ad->destinationUrl)
            @if ($destination)<a href="{{ $destination }}" @if($ad->openInNewTab) target="_blank" @endif rel="{{ $ad->rel }}">@endif
            <img src="{{ $ad->imageUrl }}" alt="{{ $ad->altText }}" width="{{ $ad->width }}" height="{{ $ad->height }}" loading="lazy">
            @if ($destination)</a>@endif
        @elseif ($ad->type === 'video')
            <video playsinline preload="metadata" @if($ad->posterUrl) poster="{{ $ad->posterUrl }}" @endif @if($ad->autoplay) autoplay @endif @if($ad->muted) muted @endif @if($ad->loop) loop @endif @if($ad->controls) controls @endif>
                <source src="{{ $ad->videoUrl }}">Your browser does not support this advertisement video.
            </video>
            @if($ad->clickUrl)<a class="ds-ad-slot__cta" href="{{ $ad->clickUrl }}" @if($ad->openInNewTab) target="_blank" @endif rel="{{ $ad->rel }}">Visit advertiser</a>@endif
        @else
            <span>ADVERTISEMENT</span><small>{{ $ad->width }} × {{ $ad->height }}</small>
        @endif

        @if($canEdit)
            <div class="ds-ad-editor" data-nosnippet>
                <button type="button" onclick="document.getElementById('ad-edit-{{ $record->uuid }}').showModal()">Edit advertisement</button>
                <a href="{{ \App\Filament\Resources\Advertisements\AdvertisementResource::getUrl('edit', ['record' => $record]) }}">Full settings</a>
            </div>
            <dialog id="ad-edit-{{ $record->uuid }}" class="ds-ad-edit-dialog">
                <form method="POST" action="{{ route('advertisements.frontend.update', $record) }}">@csrf @method('PATCH')
                    <label>Title <input name="title" value="{{ $record->title }}" required maxlength="255"></label>
                    <label>Destination URL <input name="target_url" value="{{ $record->target_url }}"></label>
                    <label>Status <select name="status"><option value="active" @selected($record->status->value === 'active')>Active</option><option value="scheduled" @selected($record->status->value === 'scheduled')>Scheduled</option><option value="paused" @selected($record->status->value === 'paused')>Paused</option></select></label>
                    <label>Start <input type="datetime-local" name="start_at" value="{{ $record->start_at?->format('Y-m-d\TH:i') }}"></label>
                    <label>End <input type="datetime-local" name="end_at" value="{{ $record->end_at?->format('Y-m-d\TH:i') }}"></label>
                    <input type="hidden" name="open_in_new_tab" value="0"><label><input type="checkbox" name="open_in_new_tab" value="1" @checked($record->open_in_new_tab)> New tab</label>
                    <input type="hidden" name="sponsored" value="0"><label><input type="checkbox" name="sponsored" value="1" @checked($record->sponsored)> Sponsored</label>
                    <input type="hidden" name="nofollow" value="0"><label><input type="checkbox" name="nofollow" value="1" @checked($record->nofollow)> Nofollow</label>
                    <button type="submit">Save</button><button type="button" onclick="this.closest('dialog').close()">Cancel</button>
                </form>
            </dialog>
        @endif
    </aside>
@endif

@once
@push('scripts')<script>(()=>{const seen=new Set;const visitor=localStorage.adVisitor||(localStorage.adVisitor=crypto.randomUUID());const observer=new IntersectionObserver(entries=>entries.forEach(entry=>{const el=entry.target,url=el.dataset.adImpression;if(entry.isIntersecting&&url&&!seen.has(url)){seen.add(url);fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},body:JSON.stringify({visitor}),keepalive:true}).catch(()=>{});observer.unobserve(el)}}),{threshold:.5});document.querySelectorAll('[data-ad-impression]').forEach(el=>observer.observe(el))})();</script>@endpush
@endonce
