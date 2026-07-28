@props(['author'])

@if ($author)
    @php
        $phoneHref = preg_replace('/[^\d+]/', '', (string) $author->mobile_number);
        $initial = mb_strtoupper(mb_substr(trim($author->name), 0, 1));
    @endphp

    <section class="ds-article-author-box" aria-labelledby="article-author-heading">
        @if ($author->avatar_url)
            <img class="ds-article-author-box__avatar" src="{{ $author->avatar_url }}"
                alt="Portrait of {{ $author->name }}" width="112" height="112" loading="lazy">
        @else
            <div class="ds-article-author-box__avatar ds-article-author-box__avatar--fallback" aria-hidden="true">
                {{ $initial }}
            </div>
        @endif

        <div class="ds-article-author-box__details">
            <p class="ds-article-author-box__label">About the author</p>
            <h2 id="article-author-heading">{{ $author->name }}</h2>

            @if (filled($author->designation))
                <p class="ds-article-author-box__designation">{{ $author->designation }}</p>
            @endif

            @if (filled($author->email) || filled($author->mobile_number))
                <address class="ds-article-author-box__contact">
                    @if (filled($author->email))
                        <a href="mailto:{{ $author->email }}">{{ $author->email }}</a>
                    @endif
                    @if (filled($author->mobile_number) && $phoneHref !== '')
                        <a href="tel:{{ $phoneHref }}">{{ $author->mobile_number }}</a>
                    @endif
                </address>
            @endif
        </div>
    </section>
@endif
