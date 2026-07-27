@php
    $logoPath = public_path('images/frontend/daily-samvad-logo.jpg');
@endphp

<a class="ds-logo" href="{{ route('home') }}" aria-label="Daily Samvad home">
    @if (is_file($logoPath))
        <img src="{{ asset('images/frontend/daily-samvad-logo.jpg') }}" alt="Daily Samvad" width="260" height="75">
    @else
        <span class="ds-logo__fallback" aria-hidden="true">Daily संवाद</span>
        <span class="ds-logo__domain" aria-hidden="true">DAILYSAMVAD.COM</span>
    @endif
</a>
