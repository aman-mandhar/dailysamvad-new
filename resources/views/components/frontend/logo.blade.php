@php
    $logoPath = public_path('images/frontend/logo.png');
@endphp

<a class="ds-logo" href="{{ route('home') }}" aria-label="Rzana Punjab home">
    @if (is_file($logoPath))
        <img src="{{ asset('images/frontend/logo.png') }}" alt="Rzana Punjab" width="260" height="75">
    @else
        <span class="ds-logo__fallback" aria-hidden="true">Rzana Punjab</span>
        <span class="ds-logo__domain" aria-hidden="true">RZANA PUNJAB</span>
    @endif
</a>
