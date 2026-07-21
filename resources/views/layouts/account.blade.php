@extends('layouts.frontend')

@section('robots', 'noindex, nofollow')

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <div class="grid gap-6 lg:grid-cols-[240px_minmax(0,1fr)]">
            <aside class="rounded-2xl border border-slate-200 bg-white p-4" aria-label="Account navigation">
                <p class="font-bold text-slate-900">{{ auth()->user()->name }}</p>
                <p class="mt-1 text-sm text-slate-500">Frontend account</p>
                <nav class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-1">
                    @foreach ([
                        'dashboard' => 'Dashboard', 'account.profile.edit' => 'Profile', 'account.security.edit' => 'Security',
                        'account.referrals' => 'Referrals', 'account.saved.index' => 'Saved Articles', 'account.history' => 'Reading History',
                        'account.notifications.index' => 'Notifications', 'account.preferences.edit' => 'Preferences',
                    ] as $routeName => $label)
                        <a href="{{ route($routeName) }}" @class(['rounded-lg px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-red-700', 'bg-red-700 text-white' => request()->routeIs($routeName), 'text-slate-700 hover:bg-slate-100' => ! request()->routeIs($routeName)])>{{ $label }}</a>
                    @endforeach
                </nav>
                <form method="POST" action="{{ route('logout') }}" class="mt-4">@csrf<button class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold" type="submit">Logout</button></form>
            </aside>
            <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 sm:p-8" aria-labelledby="account-heading">
                @yield('account-content')
            </section>
        </div>
    </div>
@endsection
