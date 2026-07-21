@extends('layouts.frontend')

@section('title', 'Reset Password')
@section('robots', 'noindex, follow')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-10 sm:px-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8" aria-labelledby="reset-heading">
            <h1 id="reset-heading" class="text-3xl font-black">Reset Password</h1>
            <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div><label for="email" class="block font-semibold">Email</label><input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autocomplete="email" class="mt-2 w-full rounded-lg border border-slate-300 p-3">@error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="password" class="block font-semibold">New password</label><input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-slate-300 p-3">@error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="password_confirmation" class="block font-semibold">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-slate-300 p-3"></div>
                <button class="w-full rounded-lg bg-red-700 px-5 py-3 font-bold text-white" type="submit">Reset password</button>
            </form>
        </section>
    </div>
@endsection
