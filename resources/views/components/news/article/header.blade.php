@props(['article'])

@php($category = $article->post->primaryCategory->first())
<header class="ds-article-header">
    @if ($category)
        <a class="ds-article-category" href="{{ route('categories.show', $category->slug) }}">{{ $category->name }}</a>
    @endif
    <h1 id="article-headline">{{ $article->post->title }}</h1>
    @if (filled($article->post->excerpt))
        <p class="ds-article-lead">{{ $article->post->excerpt }}</p>
    @endif
    <div class="ds-article-meta">
        @if ($article->post->author)
            <span class="ds-article-author">By {{ $article->post->author->name }}</span>
        @endif
        <time datetime="{{ $article->post->published_at->toIso8601String() }}">{{ $article->post->published_at->translatedFormat('d F Y, h:i A') }}</time>
        <span>{{ $article->readingTime }} min read</span>
        @if ($article->post->views_count > 0)
            <span>{{ number_format($article->post->views_count) }} views</span>
        @endif
    </div>
</header>
