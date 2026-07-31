@php
    $channelUrl = config('organization.social_links.whatsapp');
    $chatUrl = config('organization.social_links.whatsapp_chat');
@endphp

@if (filled($channelUrl) || filled($chatUrl))
    <aside class="ds-whatsapp-card" aria-label="Join Daily Samvad on WhatsApp" data-whatsapp-join>
        <div class="min-w-0">
            <strong class="block">Join Daily Samvad on WhatsApp</strong>
            <span class="text-sm text-[var(--ds-color-muted)]">Get the latest news and community updates</span>
        </div>
        <div class="ds-whatsapp-card__actions">
            @if (filled($channelUrl))
                <a class="ds-button ds-whatsapp-card__button" href="{{ $channelUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Join Daily Samvad WhatsApp channel">Join Channel</a>
            @endif
            @if (filled($chatUrl))
                <a class="ds-button ds-whatsapp-card__button" href="{{ $chatUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Join Daily Samvad WhatsApp chat">Join Chat</a>
            @endif
        </div>
    </aside>
@endif
