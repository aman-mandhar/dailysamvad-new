@extends('layouts.frontend')

@section('content')
    <div class="ds-container ds-article-page">
        <x-news.breadcrumbs :items="$article->breadcrumbs" />

        <div class="ds-article-layout">
            <article class="ds-article" aria-labelledby="article-headline">
                <x-news.article.header :article="$article" />
                <x-advertisement.slot :advertisement="$article->topAdvertisement" />
                <x-news.article.featured-image :article="$article" />
                <x-frontend.whatsapp-join />
                <x-advertisement.slot :advertisement="$article->afterFeaturedImageAdvertisement" />
                <x-news.article.share :share="$article->shareUrls" />
                <div class="my-4">
                    @auth
                        @php($bookmark = auth()->user()->bookmarks()->where('post_id', $article->post->getKey())->first())
                        @if($bookmark)
                            <form method="POST" action="{{ route('account.saved.destroy', $bookmark) }}">@csrf @method('DELETE')<button class="rounded-lg border border-slate-300 px-4 py-2 font-bold" type="submit">Remove saved article</button></form>
                        @else
                            <form method="POST" action="{{ route('account.saved.store', $article->post) }}">@csrf<button class="rounded-lg border border-slate-300 px-4 py-2 font-bold" type="submit">Save article</button></form>
                        @endif
                    @else
                        <a class="inline-block rounded-lg border border-slate-300 px-4 py-2 font-bold" href="{{ route('login') }}">Log in to save article</a>
                    @endauth
                </div>
                <x-news.article.content :blocks="$article->contentBlocks" />
                <x-news.article.author-box :author="$article->post->author" />
                <x-news.article.tags :tags="$article->post->tags" />
                <x-news.article.previous-next :previous="$article->previousPost" :next="$article->nextPost" />
                <x-news.article.related-news :posts="$article->relatedPosts" />
            </article>

            <x-news.sidebar.index :widgets="$article->sidebarWidgets" :sticky="$article->sidebarSticky" label="Article sidebar" context="article" />
        </div>
    </div>
@endsection

@push('scripts')
    @if (config('analytics.beacon_enabled'))
        <script>(()=>{const id=crypto.randomUUID(); fetch('{{ route('analytics.beacon',$article->post) }}',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({event_id:id}),keepalive:true}).catch(()=>{});})();</script>
    @endif
@endpush
