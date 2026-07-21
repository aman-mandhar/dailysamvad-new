@extends('layouts.account')
@section('title', 'Account Security')
@section('account-content')
<h1 id="account-heading" class="text-3xl font-black">Security</h1><p class="mt-2 text-slate-600">Change your password while keeping this session active.</p>
<form method="POST" action="{{ route('account.password.update') }}" class="mt-6 space-y-5">@csrf @method('PUT')
@foreach([['current_password','Current password','current-password'],['password','New password','new-password'],['password_confirmation','Confirm new password','new-password']] as [$name,$label,$autocomplete])<div><label for="{{ $name }}" class="font-semibold">{{ $label }}</label><input id="{{ $name }}" name="{{ $name }}" type="password" required autocomplete="{{ $autocomplete }}" class="mt-2 w-full rounded-lg border border-slate-300 p-3">@error($name)<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>@endforeach
<button class="rounded-lg bg-red-700 px-5 py-3 font-bold text-white">Update password</button></form>
@endsection
