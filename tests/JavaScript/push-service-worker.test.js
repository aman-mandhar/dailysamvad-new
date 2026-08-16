import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

const workerPath = new URL('../../public/firebase-messaging-sw.js', import.meta.url);

test('service worker has one background display handler and one tracked click handler', async () => {
    const source = await readFile(workerPath, 'utf8');

    assert.equal((source.match(/addEventListener\('push'/g) ?? []).length, 1);
    assert.equal((source.match(/addEventListener\('notificationclick'/g) ?? []).length, 1);
    assert.match(source, /registration\.showNotification/);
    assert.match(source, /url\.origin === self\.location\.origin/);
    assert.doesNotMatch(source, /console\.(?:log|debug)/);
});