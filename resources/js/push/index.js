import { readFirebaseConfig } from './config.js';

export function initializePushNotifications(documentObject = globalThis.document) {
    const config = readFirebaseConfig(documentObject);
    const roots = documentObject?.querySelectorAll('[data-push-opt-in]') ?? [];

    if (!config || roots.length === 0) {
        return;
    }

    import('./ui.js').then(({ initializePushUi }) => {
        roots.forEach((root) => {
            initializePushUi(root, config).catch(() => {
                // Push is progressive enhancement; failures must not affect the site.
            });
        });
    }).catch(() => {
        // A failed optional chunk must not affect other frontend features.
    });
}
