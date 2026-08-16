import assert from 'node:assert/strict';
import test from 'node:test';

import { getDeviceUuid, registerSubscription, shouldSyncSubscription, unsubscribeSubscription } from '../../resources/js/push/subscriptions.js';

test('device UUID is generated once and reused without fingerprinting', () => {
    const values = new Map();
    const storage = {
        getItem: (key) => values.get(key) ?? null,
        setItem: (key, value) => values.set(key, value),
    };
    const cryptoObject = { randomUUID: () => '123e4567-e89b-42d3-a456-426614174000' };

    assert.equal(getDeviceUuid(storage, cryptoObject), '123e4567-e89b-42d3-a456-426614174000');
    assert.equal(getDeviceUuid(storage, { randomUUID: () => 'different' }), '123e4567-e89b-42d3-a456-426614174000');
});

test('registration and unsubscribe send tokens only in CSRF-protected JSON bodies', async () => {
    const requests = [];
    const fakeFetch = async (url, options) => {
        requests.push({ url, options });
        return { ok: true, json: async () => ({ success: true }) };
    };

    await registerSubscription('fake-token-value-that-is-long-enough', false, fakeFetch);
    await unsubscribeSubscription('fake-token-value-that-is-long-enough', fakeFetch);

    assert.equal(requests[0].url, '/push/subscriptions');
    assert.equal(requests[0].options.method, 'POST');
    assert.equal(JSON.parse(requests[0].options.body).token, 'fake-token-value-that-is-long-enough');
    assert.equal(requests[1].options.method, 'DELETE');
    assert.equal(requests.every(({ url }) => !url.includes('fake-token')), true);
    assert.equal(Object.hasOwn(requests[0].options.headers, 'X-CSRF-TOKEN'), true);
});

test('subscription synchronization is throttled but authentication changes force a sync', () => {
    const now = 2_000_000;
    const storage = {
        getItem: () => JSON.stringify({ at: now - 1000, authenticated: false }),
    };

    assert.equal(shouldSyncSubscription(false, storage, now), false);
    assert.equal(shouldSyncSubscription(true, storage, now), true);
    assert.equal(shouldSyncSubscription(false, storage, now + 16 * 60 * 1000), true);
});
