import { PUSH_STATUS, browserSupportsPush, deleteMessagingToken, getMessagingToken, isPushSupported } from './messaging.js';
import { isSubscriptionLocallyDisabled, registerSubscription, shouldSyncSubscription, unsubscribeSubscription } from './subscriptions.js';
import { initializePushPreferences } from './preferences.js';

const UI_STATUS = Object.freeze({
    DEFAULT: 'default',
    SUBSCRIBED: 'subscribed',
    DISABLED: 'disabled',
    DENIED: 'denied',
    SYNC_FAILED: 'sync-failed',
    PUSH_FAILED: 'push-failed',
});

const MESSAGE_BY_STATUS = {
    [UI_STATUS.DEFAULT]: 'Daily Samvad ki important khabrein turant paayen.',
    [UI_STATUS.SUBSCRIBED]: 'News notifications enabled.',
    [UI_STATUS.DISABLED]: 'News notifications are disabled for this browser.',
    [UI_STATUS.DENIED]: 'Notifications browser settings mein blocked hain.',
    [UI_STATUS.SYNC_FAILED]: 'Unable to enable notifications right now. Please try again.',
    [UI_STATUS.PUSH_FAILED]: 'Notifications abhi enable nahi ho sakin. Dobara try karein.',
};

export function permissionUiState(permission) {
    if (permission === 'granted') return 'granted';
    if (permission === 'denied') return 'denied';
    return 'default';
}

function renderState(root, state) {
    const enableButton = root.querySelector('[data-push-enable]');
    const disableButton = root.querySelector('[data-push-disable]');
    const message = root.querySelector('[data-push-message]');

    root.hidden = false;
    message.textContent = MESSAGE_BY_STATUS[state] ?? MESSAGE_BY_STATUS[UI_STATUS.PUSH_FAILED];
    enableButton.hidden = state === UI_STATUS.SUBSCRIBED || state === UI_STATUS.DENIED;
    disableButton.hidden = state !== UI_STATUS.SUBSCRIBED;
}

async function obtainAndSync(config, forceSync = false) {
    const result = await getMessagingToken(config);
    if (result.status !== PUSH_STATUS.GRANTED || !result.token) {
        return {
            state: result.status === PUSH_STATUS.DENIED ? UI_STATUS.DENIED : UI_STATUS.PUSH_FAILED,
            token: null,
        };
    }

    try {
        if (forceSync || shouldSyncSubscription(Boolean(config.authenticated))) {
            await registerSubscription(result.token, Boolean(config.authenticated));
        }
        return { state: UI_STATUS.SUBSCRIBED, token: result.token };
    } catch {
        return { state: UI_STATUS.SYNC_FAILED, token: result.token };
    }
}

export async function initializePushUi(root, config) {
    if (!config || !browserSupportsPush()) return;

    try {
        if (!await isPushSupported()) return;
    } catch {
        return;
    }

    const enableButton = root.querySelector('[data-push-enable]');
    const disableButton = root.querySelector('[data-push-disable]');
    const permission = permissionUiState(Notification.permission);
    let currentToken = null;

    if (permission === 'denied') {
        renderState(root, UI_STATUS.DENIED);
    } else if (permission === 'granted' && isSubscriptionLocallyDisabled()) {
        renderState(root, UI_STATUS.DISABLED);
    } else {
        renderState(root, UI_STATUS.DEFAULT);
    }

    if (permission === 'granted' && !isSubscriptionLocallyDisabled()) {
        const synced = await obtainAndSync(config);
        currentToken = synced.token;
        renderState(root, synced.state);
        if (synced.state === UI_STATUS.SUBSCRIBED && currentToken) {
            initializePushPreferences(root, currentToken).catch(() => {});
        }
    }

    enableButton.addEventListener('click', async () => {
        enableButton.disabled = true;
        enableButton.textContent = 'Enabling...';

        const synced = await obtainAndSync(config, true);
        currentToken = synced.token;
        renderState(root, synced.state);
        if (synced.state === UI_STATUS.SUBSCRIBED && currentToken) {
            initializePushPreferences(root, currentToken).catch(() => {});
        }

        enableButton.disabled = false;
        enableButton.textContent = synced.state === UI_STATUS.SUBSCRIBED ? 'Enable Notifications' : 'Try Again';
    });

    disableButton.addEventListener('click', async () => {
        if (!currentToken) return;

        disableButton.disabled = true;
        disableButton.textContent = 'Disabling...';

        try {
            await unsubscribeSubscription(currentToken);
            await deleteMessagingToken(config);
            currentToken = null;
            root.querySelector('[data-push-preferences]')?.setAttribute('hidden', '');
            renderState(root, UI_STATUS.DISABLED);
            enableButton.textContent = 'Enable Notifications';
        } catch {
            renderState(root, UI_STATUS.SYNC_FAILED);
        } finally {
            disableButton.disabled = false;
            disableButton.textContent = 'Disable';
        }
    });
}
