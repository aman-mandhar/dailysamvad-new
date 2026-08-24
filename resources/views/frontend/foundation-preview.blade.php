@extends('layouts.frontend')

@section('title', 'Frontend Foundation Preview - Rzana Punjab')
@section('meta_description', 'Local development preview of Rzana Punjab frontend design primitives.')
@section('robots', 'noindex, nofollow')

@section('content')
    <div class="ds-container">
        <header class="ds-section ds-stack">
            <span class="ds-badge">Development preview</span>
            <div>
                <h1 class="text-4xl font-extrabold">Rzana Punjab frontend foundation</h1>
                <p class="mt-2 text-[var(--ds-color-muted)]">
                    यह पृष्ठ सार्वजनिक थीम की typography, layout और reusable primitives की जाँच के लिए है।
                </p>
            </div>
        </header>

        <section class="ds-section" aria-labelledby="typography-preview-heading">
            <div class="ds-section-heading">
                <h2 id="typography-preview-heading" class="ds-section-heading__title">Typography and section heading</h2>
                <span class="ds-section-heading__line" aria-hidden="true"></span>
            </div>
            <div class="ds-stack">
                <h3 class="text-3xl">हिंदी समाचार शीर्षक और English headline</h3>
                <p>
                    Rzana Punjab का मुख्य पाठ Anek Devanagari में पढ़ने योग्य, घना और समाचार-केंद्रित रहेगा।
                    LongWordsAndUnbrokenReferencesWrapSafelyWithoutCausingHorizontalPageOverflow.
                </p>
                <div class="ds-meta">
                    <span>Muskaan Dogra</span>
                    <time datetime="2026-07-18">July 18, 2026</time>
                    <span>पंजाब</span>
                </div>
            </div>
        </section>

        <hr class="ds-divider">

        <div class="ds-main-grid ds-section">
            <main class="ds-main-content ds-stack">
                <div class="ds-section-heading">
                    <h2 class="ds-section-heading__title">Main content — 780px</h2>
                    <span class="ds-section-heading__line" aria-hidden="true"></span>
                </div>

                <article class="ds-card">
                    <div class="ds-card__media ds-image-ratio-news" role="img" aria-label="16 by 9 news image placeholder">
                        <div class="ds-ad-placeholder h-full border-0">16:9 news media</div>
                    </div>
                    <div class="ds-stack p-4">
                        <span class="ds-badge self-start">पंजाब</span>
                        <h3 class="ds-card__title ds-line-clamp-2">
                            समाचार कार्ड का शीर्षक दो पंक्तियों तक स्पष्ट और सुरक्षित रूप से दिखाई देता है
                        </h3>
                        <div class="ds-card__meta">
                            <span>Rzana Punjab</span>
                            <time datetime="2026-07-18">July 18, 2026</time>
                        </div>
                    </div>
                </article>

                <article class="ds-card grid grid-cols-[110px_minmax(0,1fr)] gap-3 p-3 max-[480px]:grid-cols-[88px_minmax(0,1fr)]">
                    <div class="ds-card__media ds-image-ratio-square" role="img" aria-label="Square compact image placeholder">
                        <div class="ds-ad-placeholder h-full border-0">1:1</div>
                    </div>
                    <div class="min-w-0 self-center">
                        <span class="ds-badge">देश</span>
                        <h3 class="mt-2 text-xl font-bold leading-tight ds-line-clamp-3">Compact square news-card foundation</h3>
                        <div class="ds-meta mt-2"><span>12:30 PM</span></div>
                    </div>
                </article>
            </main>

            <aside class="ds-sidebar ds-stack" aria-label="Foundation preview sidebar">
                <div class="ds-section-heading">
                    <h2 class="ds-section-heading__title">Sidebar — 320px</h2>
                    <span class="ds-section-heading__line" aria-hidden="true"></span>
                </div>

                <div class="ds-ad-placeholder ds-image-ratio-square">Responsive advertisement placeholder</div>

                <div class="ds-whatsapp-card">
                    <div class="min-w-0">
                        <strong class="block">WhatsApp Group</strong>
                        <span class="text-sm text-[var(--ds-color-muted)]">Community updates</span>
                    </div>
                    <a class="ds-button shrink-0 !border-[var(--ds-color-whatsapp)] !bg-[var(--ds-color-whatsapp)]" href="#whatsapp-preview">Join</a>
                </div>
            </aside>
        </div>

        <section class="ds-section ds-stack" aria-labelledby="utilities-preview-heading">
            <div class="ds-section-heading">
                <h2 id="utilities-preview-heading" class="ds-section-heading__title">Utilities</h2>
                <span class="ds-section-heading__line" aria-hidden="true"></span>
            </div>
            <p class="ds-line-clamp-2">
                This deliberately long sample demonstrates the two-line truncation utility while preserving a square-corner,
                minimal-shadow newspaper appearance across desktop, tablet and mobile widths.
            </p>
            <a class="ds-button self-start" href="#main-content">Foundation button</a>
        </section>
    </div>
@endsection
