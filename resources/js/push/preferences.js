import { getDeviceUuid } from './subscriptions.js';

function csrfToken(documentObject = globalThis.document) {
    return documentObject?.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function preferenceRequest(method, token, topicIds, fetchFunction = globalThis.fetch) {
    const response = await fetchFunction('/push/preferences', {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ token, device_uuid: getDeviceUuid(), ...(topicIds === undefined ? {} : { topic_ids: topicIds }) }),
    });
    if (!response.ok) throw new Error(`Preference request failed with status ${response.status}.`);

    return response.json();
}

export function loadPushPreferences(token, fetchFunction = globalThis.fetch) {
    return preferenceRequest('POST', token, undefined, fetchFunction);
}

export function savePushPreferences(token, topicIds, fetchFunction = globalThis.fetch) {
    return preferenceRequest('PUT', token, [...new Set(topicIds.map(Number))], fetchFunction);
}

export async function initializePushPreferences(root, token) {
    const panel = root.querySelector('[data-push-preferences]');
    const list = root.querySelector('[data-push-topic-list]');
    const status = root.querySelector('[data-push-preference-status]');
    const save = root.querySelector('[data-push-preference-save]');
    if (!panel || !list || !status || !save || !token) return;

    const state = await loadPushPreferences(token);
    const selected = new Set(state.selected_topic_ids.map(Number));
    list.replaceChildren(...state.topics.map((topic) => {
        const label = document.createElement('label');
        label.className = 'ds-push-preferences__topic';
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.value = String(topic.id);
        input.checked = selected.has(Number(topic.id));
        label.append(input, document.createTextNode(topic.name));
        return label;
    }));
    status.textContent = state.configured ? 'Your device preferences are loaded.' : 'Choose topics, or leave all unchecked to receive no topic-targeted alerts.';
    panel.hidden = false;

    save.onclick = async () => {
        save.disabled = true;
        status.textContent = 'Saving preferences...';
        try {
            const ids = [...list.querySelectorAll('input:checked')].map((input) => Number(input.value));
            await savePushPreferences(token, ids);
            status.textContent = 'Notification preferences saved for this device.';
        } catch {
            status.textContent = 'Preferences could not be saved. Please try again.';
        } finally {
            save.disabled = false;
        }
    };
}
