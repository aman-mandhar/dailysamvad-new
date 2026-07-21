@extends('layouts.account')
@section('title', 'Reading History')
@section('account-content')
<h1 id="account-heading" class="text-3xl font-black">Reading History</h1><p class="mt-2 text-slate-600">Only articles read while signed in appear here.</p><div class="mt-6 space-y-4">@forelse($posts as $post)<article class="rounded-xl border p-4"><h2 class="font-bold"><a href="{{ $post->publicUrl() }}">{{ $post->title }}</a></h2><p class="text-sm text-slate-500">Last read {{ \Illuminate\Support\Carbon::parse($post->last_read_at)->diffForHumans() }}</p></article>@empty<p class="rounded-lg bg-slate-50 p-5">Your reading history is empty.</p>@endforelse</div>{{ $posts->links() }}
@endsection
