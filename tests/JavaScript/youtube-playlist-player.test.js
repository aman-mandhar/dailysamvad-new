import assert from 'node:assert/strict';
import test from 'node:test';

import { loadYouTubeIframeApi, PlaylistCursor } from '../../resources/js/frontend/youtube-playlist-player.js';

test('playlist cursor advances and restarts at index zero', () => {
    const cursor = new PlaylistCursor(3);

    assert.equal(cursor.next(), 1);
    assert.equal(cursor.next(), 2);
    assert.equal(cursor.next(), 0);
});

test('playlist cursor skips failed videos and stops after every video fails', () => {
    const cursor = new PlaylistCursor(3);

    cursor.failCurrent();
    assert.equal(cursor.next(), 1);
    cursor.failCurrent();
    assert.equal(cursor.next(), 2);
    cursor.failCurrent();
    assert.equal(cursor.next(), null);
});

test('iframe API loader appends the YouTube script only once', async () => {
    let appended = 0;
    const fakeWindow = {
        setTimeout,
        clearTimeout,
    };
    const fakeDocument = {
        querySelector: () => null,
        createElement: () => ({
            addEventListener: () => {},
        }),
        head: {
            append: () => { appended += 1; },
        },
    };

    const first = loadYouTubeIframeApi(fakeWindow, fakeDocument);
    const second = loadYouTubeIframeApi(fakeWindow, fakeDocument);

    fakeWindow.YT = { Player: class {} };
    fakeWindow.onYouTubeIframeAPIReady();

    assert.equal(first, second);
    assert.equal(await first, fakeWindow.YT);
    assert.equal(appended, 1);
});
