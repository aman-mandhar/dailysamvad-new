const DEVICE_UUID_KEY = 'newsman_device_uuid';
const REGISTRATION_KEY = 'newsman_push_registered';
const DISABLED_KEY = 'newsman_push_disabled';

function randomUuid(cryptoObject = globalThis.crypto) {
    if (typeof cryptoObject?.randomUUID === 'function') return cryptoObject.randomUUID();

    const bytes = new Uint8Array(16);
    cryptoObject.getRandomValues(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = [...bytes].map((byte) => byte.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

export function getDeviceUuid(storage = globalThis.localStorage, cryptoObject = globalThis.crypto) {
    try {
        const existing = storage.getItem(DEVICE_UUID_KEY);
        if (existing) return existing;

        const generated = randomUuid(cryptoObject);
        storage.setItem(DEVICE_UUID_KEY, generated);
        return generated;
    } catch {
        return null;
    }
}

export function getDeviceMetadata(navigatorObject = globalThis.navigator) {
    const userAgent = navigatorObject?.userAgent ?? '';
    const browserMatch = userAgent.match(/(Edg|Firefox|Chrome|CriOS|Version)\/([\d.]+)/);
    const mobile = /Android|iPhone|iPad|Mobile/i.test(userAgent);

    return {
        device_uuid: getDeviceUuid(),
        browser: browserMatch?.[1] ?? null,
        browser_version: browserMatch?.[2]?.slice(0, 32) ?? null,
        platform: navigatorObject?.userAgentData?.platform ?? navigatorObject?.platform ?? null,
        device_type: /iPad|Tablet/i.test(userAgent) ? 'tablet' : (mobile ? 'mobile' : 'desktop'),
        language: navigatorObject?.language ?? null,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone ?? null,
        permission_status: 'granted',
    };
}

function csrfToken(documentObject = globalThis.document) {
    return documentObject?.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function request(method, token, metadata = {}, fetchFunction = globalThis.fetch) {
    const response = await fetchFunction('/push/subscriptions', {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ token, ...metadata }),
    });

    if (!response.ok) throw new Error(`Push subscription request failed with status ${response.status}.`);

    return response.json();
}

export async function registerSubscription(token, authenticated = false, fetchFunction = globalThis.fetch) {
    const response = await request('POST', token, getDeviceMetadata(), fetchFunction);
    try {
        localStorage.setItem(REGISTRATION_KEY, JSON.stringify({ at: Date.now(), authenticated }));
        localStorage.removeItem(DISABLED_KEY);
    } catch {}

    return response;
}

export async function unsubscribeSubscription(token, fetchFunction = globalThis.fetch) {
    const response = await request('DELETE', token, {}, fetchFunction);
    try {
        localStorage.removeItem(REGISTRATION_KEY);
        localStorage.setItem(DISABLED_KEY, '1');
    } catch {}

    return response;
}

export function isSubscriptionLocallyDisabled(storage = globalThis.localStorage) {
    try {
        return storage.getItem(DISABLED_KEY) === '1';
    } catch {
        return false;
    }
}

export function shouldSyncSubscription(authenticated = false, storage = globalThis.localStorage, now = Date.now()) {
    try {
        const state = JSON.parse(storage.getItem(REGISTRATION_KEY) ?? 'null');
        return !state
            || state.authenticated !== authenticated
            || !Number.isFinite(state.at)
            || now - state.at >= 15 * 60 * 1000;
    } catch {
        return true;
    }
}
