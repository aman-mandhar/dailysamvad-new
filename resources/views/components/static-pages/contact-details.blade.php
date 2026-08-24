@props(['organization'])

@php
    $socialLinks = array_filter($organization['social_links'] ?? []);
@endphp

<section {{ $attributes }} aria-labelledby="contact-details-heading">
    <h2 id="contact-details-heading" class="text-2xl font-bold text-slate-950 dark:text-white">Contact Details</h2>
    <address class="mt-4 space-y-3 not-italic text-slate-700 dark:text-slate-300">
        <p><strong>Organization:</strong> {{ $organization['organization_name'] }}</p>
        <p><strong>Website:</strong> {{ $organization['website_name'] }}</p>
        @if (filled($organization['address']))<p><strong>Address:</strong> {{ $organization['address'] }}</p>@endif
        @if (filled($organization['phone']))<p><strong>Phone:</strong> <a href="tel:{{ $organization['phone'] }}">{{ $organization['phone'] }}</a></p>@endif
        @if (filled($organization['email']))<p><strong>Email:</strong> <a href="mailto:{{ $organization['email'] }}">{{ $organization['email'] }}</a></p>@endif
        @if (filled($organization['office_hours']))<p><strong>Office hours:</strong> {{ $organization['office_hours'] }}</p>@endif
        @if (filled($organization['chief_editor']))<p><strong>Chief editor:</strong> {{ $organization['chief_editor'] }}</p>@endif
    </address>
    @if ($socialLinks !== [])
        <nav class="mt-6" aria-label="Organization social links">
            <h3 class="font-semibold text-slate-950 dark:text-white">Social Links</h3>
            <ul class="mt-3 flex flex-wrap gap-3">
                @foreach ($socialLinks as $network => $url)
                    <li>
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="text-amber-700 hover:underline dark:text-amber-400" aria-label="Rzana Punjab on {{ ucfirst($network) }}">
                            {{ ucfirst($network) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
    <div class="mt-8 rounded-xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400" aria-label="Contact form placeholder">
        Contact form submission will be added in a future phase.
    </div>
</section>
