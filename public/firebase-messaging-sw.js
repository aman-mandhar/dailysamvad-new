'use strict';

// This root-scoped worker is passed explicitly to Firebase Messaging getToken().
// It intentionally contains one background push handler and one click handler.
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch {
        return;
    }

    const notification = payload?.notification ?? {};
    const data = payload?.data ?? {};
    const title = typeof notification.title === 'string' ? notification.title : null;
    if (!title) return;

    const target = data.url ?? data.tracking_url ?? payload?.fcmOptions?.link ?? null;
    event.waitUntil(self.registration.showNotification(title, {
        body: typeof notification.body === 'string' ? notification.body : undefined,
        icon: typeof notification.icon === 'string' ? notification.icon : undefined,
        image: typeof notification.image === 'string' ? notification.image : undefined,
        data: { ...data, url: target },
    }));
});

self.addEventListener('notificationclick', (event) => {
    const target = event.notification?.data?.url;
    event.notification?.close();

    if (typeof target === 'string') {
        const url = new URL(target, self.location.origin);

        if (url.origin === self.location.origin) {
            event.waitUntil(self.clients.openWindow(url.href));
        }
    }
});