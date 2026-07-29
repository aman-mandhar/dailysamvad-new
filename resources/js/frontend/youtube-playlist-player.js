const IFRAME_API_URL = 'https://www.youtube.com/iframe_api';

let iframeApiPromise;

export class PlaylistCursor {
    constructor(length) {
        this.length = Math.max(0, Number(length) || 0);
        this.index = 0;
        this.failed = new Set();
    }

    setIndex(index) {
        if (this.length === 0) return;
        this.index = ((Number(index) || 0) % this.length + this.length) % this.length;
    }

    failCurrent() {
        if (this.length > 0) this.failed.add(this.index);
    }

    next() {
        if (this.length === 0 || this.failed.size >= this.length) return null;

        for (let offset = 1; offset <= this.length; offset += 1) {
            const candidate = (this.index + offset) % this.length;
            if (!this.failed.has(candidate)) {
                this.index = candidate;
                return candidate;
            }
        }

        return null;
    }
}

export function loadYouTubeIframeApi(win = window, doc = document) {
    if (win.YT?.Player) return Promise.resolve(win.YT);
    if (iframeApiPromise) return iframeApiPromise;

    iframeApiPromise = new Promise((resolve, reject) => {
        const previousReady = win.onYouTubeIframeAPIReady;
        const timeout = win.setTimeout(() => reject(new Error('YouTube IFrame API timed out.')), 10000);

        win.onYouTubeIframeAPIReady = () => {
            win.clearTimeout(timeout);
            resolve(win.YT);
            if (typeof previousReady === 'function') previousReady();
        };

        if (!doc.querySelector(`script[src="${IFRAME_API_URL}"]`)) {
            const script = doc.createElement('script');
            script.src = IFRAME_API_URL;
            script.async = true;
            script.addEventListener('error', () => {
                win.clearTimeout(timeout);
                reject(new Error('YouTube IFrame API failed to load.'));
            }, { once: true });
            doc.head.append(script);
        }
    });

    return iframeApiPromise;
}

function initializePlayer(root) {
    if (root.dataset.youtubePlayerInitialized === 'true') return;
    root.dataset.youtubePlayerInitialized = 'true';

    const iframe = root.querySelector('iframe');
    const configElement = root.querySelector('[data-youtube-playlist-config]');
    const soundToggle = root.querySelector('[data-youtube-sound-toggle]');

    if (!iframe || !configElement) return;

    let config;
    try {
        config = JSON.parse(configElement.textContent);
    } catch {
        return;
    }

    loadYouTubeIframeApi().then((YT) => {
        let player;
        let cursor = new PlaylistCursor(config.videoIds.length);

        const playlistLength = () => config.videoIds.length || player?.getPlaylist?.()?.length || 0;
        const currentIndex = () => {
            if (config.videoIds.length) return cursor.index;
            return Math.max(0, player?.getPlaylistIndex?.() ?? 0);
        };
        const playNext = (failed = false) => {
            const length = playlistLength();
            if (length === 0) return;

            if (cursor.length !== length) cursor = new PlaylistCursor(length);
            cursor.setIndex(currentIndex());
            if (failed) cursor.failCurrent();

            const next = cursor.next();
            if (next === null) return;
            player.playVideoAt(next);
        };

        player = new YT.Player(iframe.id, {
            events: {
                onReady: (event) => {
                    if (config.muted) event.target.mute();

                    if (config.videoIds.length) {
                        const method = config.autoplay ? 'loadPlaylist' : 'cuePlaylist';
                        event.target[method]({ playlist: config.videoIds, index: 0, startSeconds: 0 });
                    } else if (config.autoplay) {
                        event.target.playVideo();
                    }
                },
                onStateChange: (event) => {
                    if (event.data === YT.PlayerState.ENDED) {
                        if (config.loop) playNext();
                    }
                },
                onError: () => playNext(true),
            },
        });

        soundToggle?.addEventListener('click', () => {
            const muted = player.isMuted();
            if (muted) player.unMute(); else player.mute();
            soundToggle.textContent = muted ? 'Mute' : 'Unmute';
            soundToggle.setAttribute('aria-label', muted ? 'Mute video news player' : 'Unmute video news player');
            soundToggle.setAttribute('aria-pressed', muted ? 'true' : 'false');
        });
    }).catch(() => {
        // The server-rendered privacy-enhanced playlist iframe remains usable.
    });
}

export function initializeYouTubePlaylistPlayers(doc = document) {
    const roots = [...doc.querySelectorAll('[data-youtube-playlist-player]')];
    if (roots.length === 0) return;

    if (!('IntersectionObserver' in window)) {
        roots.forEach(initializePlayer);
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            observer.unobserve(entry.target);
            initializePlayer(entry.target);
        });
    }, { rootMargin: '250px 0px' });

    roots.forEach((root) => observer.observe(root));
}
