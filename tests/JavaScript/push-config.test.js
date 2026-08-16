import assert from 'node:assert/strict';
import test from 'node:test';

import { isFirebaseConfigComplete, readFirebaseConfig } from '../../resources/js/push/config.js';
import { browserSupportsPush } from '../../resources/js/push/messaging.js';
import { permissionUiState } from '../../resources/js/push/ui.js';

const completeConfig = {
    apiKey: 'api-key',
    authDomain: 'example.firebaseapp.com',
    projectId: 'example',
    messagingSenderId: '123',
    appId: 'app-id',
    vapidKey: 'public-vapid-key',
};

test('Firebase browser configuration requires every messaging value', () => {
    assert.equal(isFirebaseConfigComplete(completeConfig), true);
    assert.equal(isFirebaseConfigComplete({ ...completeConfig, vapidKey: '' }), false);
    assert.equal(isFirebaseConfigComplete(null), false);
});

test('Firebase configuration is read safely from its JSON element', () => {
    const documentObject = {
        getElementById: () => ({ textContent: JSON.stringify(completeConfig) }),
    };

    assert.deepEqual(readFirebaseConfig(documentObject), completeConfig);
    assert.equal(readFirebaseConfig({ getElementById: () => ({ textContent: '{bad json' }) }), null);
});

test('push capability and permission states are predictable', () => {
    assert.equal(browserSupportsPush({ Notification: {} }, { serviceWorker: {} }), true);
    assert.equal(browserSupportsPush({}, { serviceWorker: {} }), false);
    assert.equal(permissionUiState('granted'), 'granted');
    assert.equal(permissionUiState('denied'), 'denied');
    assert.equal(permissionUiState('default'), 'default');
});
