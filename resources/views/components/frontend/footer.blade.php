@php
    $organization = config('organization');
@endphp

<footer class="mt-12 border-t border-slate-800 bg-slate-950 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
        <section aria-labelledby="footer-about-heading">
            <h2 id="footer-about-heading" class="font-semibold text-white">About</h2>
            <p class="mt-3 text-sm text-slate-400">{{ $organization['website_name'] }} multilingual news platform.</p>
        </section>
        <nav aria-label="Policy links">
            <h2 class="font-semibold text-white">Policies</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('pages.copyright') }}">Copyright Policy</a></li>
                <li><a href="{{ route('pages.fact-checking') }}">Fact-Checking Policy</a></li>
                <li><a href="{{ route('pages.editorial') }}">Editorial Policy</a></li>
                <li><a href="{{ route('pages.disclaimer') }}">Disclaimer</a></li>
                <li><a href="{{ route('pages.terms') }}">Terms and Conditions</a></li>
                <li><a href="{{ route('pages.privacy') }}">Privacy Policy</a></li>
            </ul>
        </nav>
        <nav aria-label="Useful links">
            <h2 class="font-semibold text-white">Useful Links</h2>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('pages.advertising') }}">Advertising Policy</a></li>
                <li><a href="{{ route('pages.grievance') }}">Grievance Redressal</a></li>
                <li><a href="{{ route('pages.dmca') }}">DMCA Policy</a></li>
                <li><a href="{{ route('pages.about') }}">About Us</a></li>
                <li><a href="{{ route('pages.contact') }}">Contact Us</a></li>
            </ul>
        </nav>
        <x-advertisement.slot :position="\App\Enums\AdvertisementPosition::FooterTop" :context="['page_type' => 'footer']" />
    </div>
    <div class="border-t border-slate-800 px-4 py-5 text-center text-sm text-slate-400">
        &copy; {{ now()->year }} {{ $organization['organization_name'] }}. All rights reserved.
    </div>
</footer>
