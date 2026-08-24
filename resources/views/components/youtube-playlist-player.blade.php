@if ($layout === 'grid')
@if ($videos() !== [])
<section class="ds-video-grid-panel" data-youtube-video-grid data-player-placement="{{ $placement }}" aria-labelledby="{{ $playerId }}-heading">
    <h2 id="{{ $playerId }}-heading" class="sr-only">Latest playlist videos</h2>
    <div class="ds-video-grid">
        @foreach ($videos() as $videoId)
            <div class="ds-video-grid__item">
                <iframe
                    src="{{ $videoEmbedUrl($videoId) }}"
                    title="Rzana Punjab video news {{ $loop->iteration }}"
                    allow="encrypted-media; picture-in-picture"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"
                ></iframe>
            </div>
        @endforeach
    </div>
</section>
@endif
@else
<section
    class="ds-sidebar-widget ds-video-player"
    data-youtube-playlist-player
    data-player-placement="{{ $placement }}"
    aria-labelledby="{{ $playerId }}-heading"
>
    
    <div class="ds-video-player__frame">
        <iframe
            id="{{ $playerId }}"
            src="{{ $embedUrl() }}"
            title="Rzana Punjab video news playlist"
            allow="autoplay; encrypted-media; picture-in-picture"
            allowfullscreen
            loading="eager"
            referrerpolicy="strict-origin-when-cross-origin"
        ></iframe>
    </div>

    <div class="ds-video-player__actions">
        <button
            class="ds-video-player__sound"
            type="button"
            data-youtube-sound-toggle
            aria-label="Unmute video news player"
            aria-pressed="false"
        >
            Unmute
        </button>
    </div>

    <script type="application/json" data-youtube-playlist-config>{!! \Illuminate\Support\Js::encode($playerConfig) !!}</script>
</section>
@endif
