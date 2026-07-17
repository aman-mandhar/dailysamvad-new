@props(['share'])

<div class="ds-article-share" data-article-share>
    <span>Share</span>
    @foreach (['facebook' => 'Facebook', 'x' => 'X', 'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram', 'email' => 'Email'] as $network => $label)
        <a href="{{ $share[$network] }}" @if($network !== 'email') target="_blank" rel="noopener noreferrer" @endif aria-label="Share on {{ $label }}">{{ $label }}</a>
    @endforeach
    <button type="button" data-copy-link data-url="{{ $share['canonical'] }}" aria-label="Copy article link">Copy link</button>
    <span class="ds-share-status" data-copy-status aria-live="polite"></span>
</div>
