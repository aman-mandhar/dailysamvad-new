@extends('layouts.account')
@section('title', 'My Dashboard')
@section('account-content')
    <h1 id="account-heading" class="text-3xl font-black">My Dashboard</h1>
    <dl class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ([
            ['Current role', $roleLabel],
            ['Account status', $user->is_active ? 'Active' : 'Inactive'],
            ['Referral code', $user->refcode], ['Direct referrals', number_format($referralCount)],
            ['Saved articles', number_format($bookmarkCount)], ['Recently read', number_format($historyCount)],
            ['Unread notifications', number_format($unreadCount)],
        ] as [$label, $value])
            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-sm font-semibold text-slate-500">{{ $label }}</dt><dd class="mt-1 break-words text-lg font-bold">{{ $value }}</dd></div>
        @endforeach
    </dl>
    <div class="mt-6 flex flex-wrap gap-3"><a class="rounded-lg bg-red-700 px-4 py-2 font-bold text-white" href="{{ route('account.profile.edit') }}">Complete Profile</a><a class="rounded-lg border border-slate-300 px-4 py-2 font-bold" href="{{ route('account.saved.index') }}">View Saved Articles</a></div>
@endsection
