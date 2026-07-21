@extends('layouts.frontend')

@section('title', 'Forgot Password')
@section('robots', 'noindex, follow')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-10 sm:px-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8" aria-labelledby="forgot-heading">
            <h1 id="forgot-heading" class="text-3xl font-black">Forgot Password</h1>
            <p class="mt-3 text-slate-600">Enter your email address and, if an account is eligible, we will send password-reset instructions.</p>
            @if (session('status'))<p class="mt-4 text-sm text-green-700" role="status">{{ session('status') }}</p>@endif
            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
                @csrf
                <div><label for="email" class="block font-semibold">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" autofocus class="mt-2 w-full rounded-lg border border-slate-300 p-3">@error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <button class="w-full rounded-lg bg-red-700 px-5 py-3 font-bold text-white" type="submit">Send reset link</button>
            </form>
        </section>
    </div>
@endsection
