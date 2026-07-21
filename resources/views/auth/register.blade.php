@extends('layouts.frontend')

@section('title', 'Create Account')
@section('robots', 'noindex, follow')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-10 sm:px-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8" aria-labelledby="register-heading">
            <h1 id="register-heading" class="text-3xl font-black text-slate-950">Create Account</h1>
            <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
                @csrf
                <div><label for="name" class="block font-semibold">Name</label><input id="name" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus class="mt-2 w-full rounded-lg border border-slate-300 p-3">@error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="email" class="block font-semibold">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-2 w-full rounded-lg border border-slate-300 p-3">@error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="referral_code" class="block font-semibold">Referral code <span class="font-normal text-slate-500">(optional)</span></label><input id="referral_code" name="referral_code" value="{{ old('referral_code', request('ref')) }}" maxlength="20" class="mt-2 w-full rounded-lg border border-slate-300 p-3">@error('referral_code')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="password" class="block font-semibold">Password</label><input id="password" name="password" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-slate-300 p-3"><p class="mt-1 text-xs text-slate-500">At least 12 characters with uppercase, lowercase, a number, and a symbol.</p>@error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="password_confirmation" class="block font-semibold">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-2 w-full rounded-lg border border-slate-300 p-3"></div>
                <button class="w-full rounded-lg bg-red-700 px-5 py-3 font-bold text-white" type="submit">Register</button>
            </form>
            <p class="mt-6 text-sm">Already registered? <a class="text-red-700 underline" href="{{ route('login') }}">Login</a></p>
        </section>
    </div>
@endsection
