@php
    $organization = config('organization');
    $socialLabels = [
        'facebook' => ['Facebook', 'f'],
        'instagram' => ['Instagram', '◎'],
        'youtube' => ['YouTube', '▶'],
        'whatsapp' => ['WhatsApp', '◉'],
    ];
    $socialLinks = collect($socialLabels)
        ->map(fn (array $meta, string $network) => [
            'network' => $network,
            'label' => $meta[0],
            'icon' => $meta[1],
            'url' => $organization['social_links'][$network] ?? null,
        ])
        ->filter(fn (array $link) => filled($link['url']));
@endphp

<footer class="ds-footer">
    <x-advertisement.slot :position="\App\Enums\AdvertisementPosition::FooterTop" :context="['page_type' => 'footer']" />

    <div class="ds-container ds-footer__grid">
        <section aria-labelledby="footer-about-heading">
            <h2 id="footer-about-heading" class="ds-footer__heading">About Us</h2>
            <a class="ds-footer__logo" href="{{ route('home') }}" aria-label="Rzana Punjab home">
                <img src="{{ asset('images/frontend/logo.png') }}" alt="Rzana Punjab" width="260" height="75">
            </a>
            <p class="ds-footer__about">
                Rzana Punjab — पत्रकारिता में जज़्बा, सिर्फ़ सच। Rzana Punjab is an online news portal dedicated to news that matters to us while celebrating the views and thoughts of a common man.
            </p>
        </section>

        <section aria-labelledby="footer-visit-heading">
            <h2 id="footer-visit-heading" class="ds-footer__heading">Visit Us</h2>
            <address class="ds-footer__contact">
                @if (filled($organization['phone']))
                    <a href="tel:{{ preg_replace('/[^+0-9]/', '', $organization['phone']) }}"><span aria-hidden="true">☎</span>{{ $organization['phone'] }}</a>
                @endif
                @if (filled($organization['email']))
                    <a href="mailto:{{ $organization['email'] }}"><span aria-hidden="true">✉</span>{{ $organization['email'] }}</a>
                @endif
                @if (filled($organization['address']))
                    <p><span aria-hidden="true">◆</span>{{ $organization['address'] }}</p>
                @endif
                @if (filled($organization['office_hours']))
                    <p><span aria-hidden="true">●</span>{{ $organization['office_hours'] }}</p>
                @endif
            </address>
        </section>

        <nav aria-labelledby="footer-social-heading">
            <h2 id="footer-social-heading" class="ds-footer__heading">Contact Us</h2>
            <ul class="ds-footer__links ds-footer__social-links">
                @foreach ($socialLinks as $link)
                    <li>
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">
                            <span class="ds-footer__social-icon ds-footer__social-icon--{{ $link['network'] }}" aria-hidden="true">{{ $link['icon'] }}</span>
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        <nav aria-labelledby="footer-quick-links-heading">
            <h2 id="footer-quick-links-heading" class="ds-footer__heading">Quick Links</h2>
            <ul class="ds-footer__links ds-footer__quick-links">
                <li><a href="{{ route('pages.copyright') }}">Copyright Policy</a></li>
                <li><a href="{{ route('pages.fact-checking') }}">Fact-Checking Policy</a></li>
                <li><a href="{{ route('pages.editorial') }}">Editorial Policy</a></li>
                <li><a href="{{ route('pages.disclaimer') }}">Disclaimer</a></li>
                <li><a href="{{ route('pages.terms') }}">Terms and Conditions</a></li>
                <li><a href="{{ route('pages.privacy') }}">Privacy Policy</a></li>
                <li><a href="{{ route('pages.about') }}">About Us</a></li>
                <li><a href="{{ route('pages.contact') }}">Contact Us</a></li>
            </ul>
        </nav>
    </div>


    <div class="ds-footer__copyright">
        <div class="ds-container">
            <span>Copyright &copy; {{ now()->year }} {{ $organization['organization_name'] }}. All rights reserved.</span>
            <span class="ds-footer__developer">Developed by <a href="https://parthtechmantra.in" target="_blank" rel="noopener noreferrer">Parth Tech Mantra</a></span>
        </div>
    </div>
</footer>
