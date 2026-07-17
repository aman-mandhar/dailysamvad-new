<aside {{ $attributes->class('space-y-6') }} aria-label="Homepage sidebar">
    <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900" aria-labelledby="popular-news-heading">
        <h2 id="popular-news-heading" class="font-bold text-slate-950 dark:text-white">Popular News</h2>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Popular stories will appear here.</p>
    </section>

    <x-frontend.advertisements.sidebar />

    <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900" aria-labelledby="newsletter-heading">
        <h2 id="newsletter-heading" class="font-bold text-slate-950 dark:text-white">Newsletter</h2>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Newsletter signup will be available soon.</p>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900" aria-labelledby="weather-heading">
        <h2 id="weather-heading" class="font-bold text-slate-950 dark:text-white">Weather</h2>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Local weather information will appear here.</p>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900" aria-labelledby="trending-heading">
        <h2 id="trending-heading" class="font-bold text-slate-950 dark:text-white">Trending</h2>
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">Trending topics will appear here.</p>
    </section>
</aside>
