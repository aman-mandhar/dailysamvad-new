@props(['archive'])

<header class="ds-archive-header">
    @if ($archive->contextType === 'author' && $archive->authorAvatarUrl)
        <img class="ds-archive-author-avatar" src="{{ $archive->authorAvatarUrl }}" alt="{{ $archive->title }}" width="96" height="96" loading="lazy">
    @endif
    <p class="ds-archive-label">{{ $archive->label }}</p>
    <h1 id="archive-heading" class="ds-archive-title">{{ $archive->title }}</h1>
    @if ($archive->description)
        <p class="ds-archive-description">{{ $archive->description }}</p>
    @endif
    <p class="ds-archive-count">{{ trans_choice('{0} No published articles|{1} :count published article|[2,*] :count published articles', $archive->posts->total(), ['count' => number_format($archive->posts->total())]) }}</p>
    @if ($archive->authorSocialLinks !== [])
        <nav class="ds-archive-author-socials" aria-label="Author social links">
            @foreach ($archive->authorSocialLinks as $social)
                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer">{{ $social['label'] }}</a>
            @endforeach
        </nav>
    @endif
</header>
