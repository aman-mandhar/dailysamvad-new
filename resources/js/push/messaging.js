import { deleteToken, getMessaging, getToken, isSupported } from 'firebase/messaging';

import { getFirebaseApp } from './firebase.js';

export const PUSH_STATUS = Object.freeze({
    GRANTED: 'granted',
    DENIED: 'denied',
    UNSUPPORTED: 'unsupported',
    CONFIGURATION_MISSING: 'configuration-missing',
    REGISTRATION_FAILED: 'registration-failed',
    TOKEN_FAILED: 'token-failed',
});

export function browserSupportsPush(windowObject = globalThis.window, navigatorObject = globalThis.navigator) {
    return Boolean(windowObject && 'Notification' in windowObject && navigatorObject && 'serviceWorker' in navigatorObject);
}

export async function isPushSupported() {
    return browserSupportsPush() && window.isSecureContext && await isSupported();
}

export async function registerMessagingServiceWorker() {
    if (!browserSupportsPush() || !window.isSecureContext) {
        return null;
    }

    return navigator.serviceWorker.register('/firebase-messaging-sw.js', { scope: '/' });
}

export async function requestNotificationPermission() {
    if (Notification.permission !== 'default') {
        return Notification.permission;
    }

    return Notification.requestPermission();
}

export async function getMessagingToken(config) {
    if (!config) {
        return { status: PUSH_STATUS.CONFIGURATION_MISSING, token: null };
    }

    if (!await isPushSupported()) {
        return { status: PUSH_STATUS.UNSUPPORTED, token: null };
    }

    const permission = await requestNotificationPermission();
    if (permission !== 'granted') {
        return { status: PUSH_STATUS.DENIED, token: null };
    }

    let registration;
    try {
        registration = await registerMessagingServiceWorker();
    } catch {
        return { status: PUSH_STATUS.REGISTRATION_FAILED, token: null };
    }

    try {
        const messaging = getMessaging(getFirebaseApp(config));
        const token = await getToken(messaging, {
            vapidKey: config.vapidKey,
            serviceWorkerRegistration: registration,
        });

        return token
            ? { status: PUSH_STATUS.GRANTED, token }
            : { status: PUSH_STATUS.TOKEN_FAILED, token: null };
    } catch {
        return { status: PUSH_STATUS.TOKEN_FAILED, token: null };
    }
}

export async function deleteMessagingToken(config) {
    try {
        return await deleteToken(getMessaging(getFirebaseApp(config)));
    } catch {
        return false;
    }
}
