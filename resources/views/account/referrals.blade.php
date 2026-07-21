@extends('layouts.account')
@section('title', 'Referrals')
@section('account-content')
<h1 id="account-heading" class="text-3xl font-black">Referrals</h1><div class="mt-5 rounded-xl bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-500">Referral code</p><p class="font-mono text-xl font-bold">{{ $user->refcode }}</p><label for="referral-link" class="mt-4 block text-sm font-semibold">Referral link</label><input id="referral-link" readonly value="{{ $referralLink }}" class="mt-1 w-full rounded-lg border p-3"></div>
<p class="mt-5 font-semibold">{{ $referrals->total() }} direct referrals · {{ $thisMonth }} this month</p>
<div class="mt-4 space-y-3">@forelse($referrals as $referral)<article class="rounded-lg border p-4"><h2 class="font-bold">{{ Str::mask($referral->name, '*', 1) }}</h2><p class="text-sm text-slate-500">Joined {{ $referral->created_at->format('d M Y') }}</p></article>@empty<p class="rounded-lg bg-slate-50 p-5">No direct referrals yet.</p>@endforelse</div>{{ $referrals->links() }}
@endsection
