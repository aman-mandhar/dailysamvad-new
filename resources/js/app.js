import { initializeHeaderSearch } from './frontend/header-search';
import { initializeNavigation } from './frontend/navigation';
import { initializeTickers } from './frontend/ticker';
import { initializeLeadSliders } from './frontend/lead-slider';
import { initializeArticleShare } from './frontend/article-share';
import { initializeYouTubePlaylistPlayers } from './frontend/youtube-playlist-player';

document.addEventListener('DOMContentLoaded', () => {
    document.documentElement.classList.add('js');
    initializeNavigation();
    initializeHeaderSearch();
    initializeTickers();
    initializeLeadSliders();
    initializeArticleShare();
});

window.addEventListener('load', () => initializeYouTubePlaylistPlayers(), { once: true });
