const REQUIRED_FIREBASE_KEYS = [
    'apiKey',
    'authDomain',
    'projectId',
    'messagingSenderId',
    'appId',
    'vapidKey',
];

export function isFirebaseConfigComplete(config) {
    return Boolean(config && REQUIRED_FIREBASE_KEYS.every((key) => typeof config[key] === 'string' && config[key].trim() !== ''));
}

export function readFirebaseConfig(documentObject = globalThis.document) {
    const element = documentObject?.getElementById('firebase-web-config');

    if (!element) {
        return null;
    }

    try {
        const config = JSON.parse(element.textContent || '{}');
        return isFirebaseConfigComplete(config) ? config : null;
    } catch {
        return null;
    }
}
