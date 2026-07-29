<section
    class="ds-sidebar-widget ds-video-player"
    data-youtube-playlist-player
    data-player-placement="{{ $placement }}"
    aria-labelledby="{{ $playerId }}-heading"
>
    <h2 id="{{ $playerId }}-heading" class="ds-sidebar-widget__title">Video News</h2>

    <div class="ds-video-player__frame">
        <iframe
            id="{{ $playerId }}"
            src="{{ $embedUrl() }}"
            title="Daily Samvad video news playlist"
            allow="autoplay; encrypted-media; picture-in-picture"
            allowfullscreen
            loading="lazy"
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
        <a href="{{ $playlistUrl() }}" target="_blank" rel="noopener noreferrer">
            Watch playlist on YouTube
        </a>
    </div>

    <script type="application/json" data-youtube-playlist-config>{!! \Illuminate\Support\Js::encode($playerConfig) !!}</script>
</section>
