@if ($posts->isNotEmpty())
    <section class="mt-12 text-left" aria-labelledby="not-found-latest-heading">
        <x-frontend.section-heading id="not-found-latest-heading">Latest News</x-frontend.section-heading>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)<x-news.medium-card :post="$post" />@endforeach
        </div>
    </section>
@endif
