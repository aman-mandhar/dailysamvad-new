@extends('layouts.frontend')

@section('title', 'Login')
@section('robots', 'noindex, follow')

@section('content')
    <div class="mx-auto max-w-lg px-4 py-10 sm:px-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 sm:p-8" aria-labelledby="login-heading">
            <h1 id="login-heading" class="text-3xl font-black text-slate-950">Login</h1>
            @if (session('status'))<p class="mt-4 text-sm text-green-700" role="status">{{ session('status') }}</p>@endif
            <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
                @csrf
                <div><label for="email" class="block font-semibold">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" autofocus class="mt-2 w-full rounded-lg border border-slate-300 p-3">@error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <div><label for="password" class="block font-semibold">Password</label><input id="password" name="password" type="password" required autocomplete="current-password" class="mt-2 w-full rounded-lg border border-slate-300 p-3">@error('password')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror</div>
                <label class="flex items-center gap-2"><input name="remember" type="checkbox" value="1"> Remember me</label>
                <button class="w-full rounded-lg bg-red-700 px-5 py-3 font-bold text-white" type="submit">Login</button>
            </form>
            <div class="mt-6 flex flex-wrap justify-between gap-3 text-sm"><a class="text-red-700 underline" href="{{ route('password.request') }}">Forgot password?</a><a class="text-red-700 underline" href="{{ route('register') }}">Create account</a></div>
        </section>
    </div>
@endsection
