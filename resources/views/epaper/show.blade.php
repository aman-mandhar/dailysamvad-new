<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $epaper->post->title }} | Daily Samvad ePaper</title>
        <meta name="robots" content="noindex, follow">
        <link rel="canonical" href="{{ $epaper->canonicalUrl }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="ds-epaper-page" data-epaper-page>
        <header class="ds-epaper-toolbar" data-epaper-controls>
            <div>
                <span class="ds-epaper-toolbar__badge">ePaper</span>
                <span class="ds-epaper-toolbar__mode">Reading mode</span>
            </div>
            <nav aria-label="ePaper actions">
                <a href="{{ $epaper->canonicalUrl }}">Back to article</a>
                <button type="button" data-epaper-share-page>Share</button>
            </nav>
        </header>

        <main class="ds-epaper-shell">
            <div class="ds-epaper-actions" data-epaper-controls>
                <button type="button" data-epaper-print>Print</button>
                <button type="button" data-epaper-download>Download JPEG</button>
                <button type="button" data-epaper-share-jpeg>Share JPEG</button>
                <p class="ds-epaper-status" data-epaper-status role="status" aria-live="polite"></p>
            </div>

            <article class="ds-epaper-sheet" data-epaper-sheet data-epaper-filename="{{ $epaper->post->slug }}">
                <header class="ds-epaper-masthead">
                    <img src="{{ asset('images/epaper-header.png') }}" alt="Daily Samvad ePaper" width="593" height="147">
                    <div class="ds-epaper-masthead__rule"></div>
                    <p>{{ $epaper->post->published_at?->timezone(config('app.display_timezone', 'Asia/Kolkata'))->translatedFormat('l, d F Y') }}</p>
                </header>

                <div class="ds-epaper-story">
                    @if ($category = $epaper->post->primaryCategory->first())
                        <p class="ds-epaper-story__category">{{ $category->name }}</p>
                    @endif
                    <h2>{{ $epaper->post->title }}</h2>
                    <p class="ds-epaper-story__byline">
                        By {{ $epaper->post->author->name }}
                        <span aria-hidden="true">•</span>
                        {{ $epaper->post->published_at?->timezone(config('app.display_timezone', 'Asia/Kolkata'))->format('d M Y, h:i A') }}
                    </p>

                    @php($image = $epaper->post->responsiveFeaturedImage())
                    @if ($image['src'])
                        <figure class="ds-epaper-story__featured-image">
                            <x-news.responsive-image :src="$image['src']" :srcset="$image['srcset']" sizes="(max-width: 900px) 100vw, 980px" :width="$image['width']" :height="$image['height']" :alt="$epaper->post->featured_image_alt ?: $epaper->post->title" loading="eager" fetchpriority="high" :fallback="false" />
                            @if (filled($epaper->post->featured_image_caption))
                                <figcaption>{{ $epaper->post->featured_image_caption }}</figcaption>
                            @endif
                        </figure>
                    @endif

                    <div class="ds-epaper-story__content">
                        @foreach ($epaper->contentBlocks as $block)
                            {!! $block->html !!}
                        @endforeach
                    </div>
                </div>
            </article>
        </main>
    </body>
</html>
