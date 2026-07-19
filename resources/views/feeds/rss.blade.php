{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
<channel>
    <title>{{ config('organization.website_name') }}</title>
    <link>{{ route('home') }}</link>
    <description>Latest published news from {{ config('organization.website_name') }}.</description>
    <language>{{ app()->getLocale() }}</language>
    <lastBuildDate>{{ now()->toRssString() }}</lastBuildDate>
    @foreach ($posts as $post)
        <item>
            <title>{{ $post->title }}</title>
            <link>{{ $post->publicUrl() }}</link>
            <guid isPermaLink="true">{{ $post->publicUrl() }}</guid>
            <description>{{ $post->effectiveMetaDescription() }}</description>
            <pubDate>{{ $post->published_at->toRssString() }}</pubDate>
            @if ($post->author)<author>{{ $post->author->name }}</author>@endif
            @if ($post->primaryCategory->isNotEmpty())<category>{{ $post->primaryCategory->first()->name }}</category>@endif
            @if ($post->featured_image_url)
                <media:content url="{{ $post->featured_image_url }}" medium="image" />
            @endif
        </item>
    @endforeach
</channel>
</rss>
