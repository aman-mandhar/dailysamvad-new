@props(['data'])

@php
    $openGraph = $data->openGraph;
    $twitter = $data->twitter;
@endphp

<title>{{ $data->title }}</title>
<meta name="description" content="{{ $data->description }}">
@if ($data->keywords !== [])
    <meta name="keywords" content="{{ implode(', ', $data->keywords) }}">
@endif
<meta name="author" content="{{ $data->author }}">
<meta name="robots" content="{{ $data->robots }}">
<link rel="canonical" href="{{ $data->canonical }}">

<meta property="og:type" content="{{ $openGraph->type }}">
<meta property="og:site_name" content="{{ $openGraph->siteName }}">
<meta property="og:locale" content="{{ $openGraph->locale }}">
<meta property="og:title" content="{{ $openGraph->title }}">
<meta property="og:description" content="{{ $openGraph->description }}">
<meta property="og:url" content="{{ $openGraph->url }}">
@if ($openGraph->image)
    <meta property="og:image" content="{{ $openGraph->image->url }}">
    @if (str_starts_with($openGraph->image->url, 'https://'))
        <meta property="og:image:secure_url" content="{{ $openGraph->image->url }}">
    @endif
    @if ($openGraph->image->mimeType)
        <meta property="og:image:type" content="{{ $openGraph->image->mimeType }}">
    @endif
    @if ($openGraph->image->width)
        <meta property="og:image:width" content="{{ $openGraph->image->width }}">
    @endif
    @if ($openGraph->image->height)
        <meta property="og:image:height" content="{{ $openGraph->image->height }}">
    @endif
    @if ($openGraph->image->alt)
        <meta property="og:image:alt" content="{{ $openGraph->image->alt }}">
    @endif
@endif
@if ($openGraph->publishedTime)
    <meta property="article:published_time" content="{{ $openGraph->publishedTime }}">
@endif
@if ($openGraph->modifiedTime)
    <meta property="article:modified_time" content="{{ $openGraph->modifiedTime }}">
@endif
@if ($openGraph->authorUrl)
    <meta property="article:author" content="{{ $openGraph->authorUrl }}">
@endif
@if ($openGraph->publisherUrl)
    <meta property="article:publisher" content="{{ $openGraph->publisherUrl }}">
@endif
@if ($openGraph->section)
    <meta property="article:section" content="{{ $openGraph->section }}">
@endif
@foreach ($openGraph->tags as $tag)
    <meta property="article:tag" content="{{ $tag }}">
@endforeach

<meta name="twitter:card" content="{{ $twitter->card }}">
<meta name="twitter:title" content="{{ $twitter->title }}">
<meta name="twitter:description" content="{{ $twitter->description }}">
@if ($twitter->image)
    <meta name="twitter:image" content="{{ $twitter->image->url }}">
    @if ($twitter->image->alt)
        <meta name="twitter:image:alt" content="{{ $twitter->image->alt }}">
    @endif
@endif
@if ($twitter->site)
    <meta name="twitter:site" content="{{ $twitter->site }}">
@endif
@if ($twitter->creator)
    <meta name="twitter:creator" content="{{ $twitter->creator }}">
@endif

@if ($data->schema->nodes !== [])
    <script type="application/ld+json">{!! $data->schema->toJson() !!}</script>
@endif
