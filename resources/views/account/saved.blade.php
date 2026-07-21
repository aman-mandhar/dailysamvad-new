@extends('layouts.account')
@section('title', 'Saved Articles')
@section('account-content')
<h1 id="account-heading" class="text-3xl font-black">Saved Articles</h1><div class="mt-6 space-y-4">@forelse($bookmarks as $bookmark)@php($post=$bookmark->post)<article class="rounded-xl border border-slate-200 p-4"><h2 class="text-lg font-bold"><a class="hover:text-red-700" href="{{ $post->publicUrl() }}">{{ $post->title }}</a></h2><p class="mt-1 text-sm text-slate-500">Saved {{ $bookmark->created_at->diffForHumans() }} · Published {{ $post->published_at->format('d M Y') }}</p><form method="POST" action="{{ route('account.saved.destroy',$bookmark) }}" class="mt-3">@csrf @method('DELETE')<button class="text-sm font-bold text-red-700 underline">Remove bookmark</button></form></article>@empty<p class="rounded-lg bg-slate-50 p-5">You have not saved any articles yet.</p>@endforelse</div>{{ $bookmarks->links() }}
@endsection
