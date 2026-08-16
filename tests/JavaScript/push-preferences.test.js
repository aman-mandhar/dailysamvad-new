import assert from 'node:assert/strict';
import test from 'node:test';

import { loadPushPreferences, savePushPreferences } from '../../resources/js/push/preferences.js';

test('preference requests keep the token in a CSRF-protected request body and deduplicate topics', async () => {
    const requests = [];
    globalThis.localStorage = { getItem: () => '11111111-1111-4111-8111-111111111111', setItem: () => {} };
    globalThis.document = { querySelector: () => ({ content: 'csrf' }) };
    const fetcher = async (url, options) => {
        requests.push({ url, options });
        return { ok: true, json: async () => ({ topics: [], selected_topic_ids: [] }) };
    };

    await loadPushPreferences('fake-token-with-enough-characters', fetcher);
    await savePushPreferences('fake-token-with-enough-characters', [2, 2, 3], fetcher);

    assert.equal(requests[0].url, '/push/preferences');
    assert.equal(requests[0].options.method, 'POST');
    assert.equal(requests[0].options.headers['X-CSRF-TOKEN'], 'csrf');
    assert.deepEqual(JSON.parse(requests[1].options.body).topic_ids, [2, 3]);
    assert.equal(requests[0].url.includes('fake-token'), false);
});
