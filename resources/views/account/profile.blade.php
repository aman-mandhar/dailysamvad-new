@extends('layouts.account')
@section('title', 'Profile')
@section('account-content')
<h1 id="account-heading" class="text-3xl font-black">Profile</h1>
<form method="POST" action="{{ route('account.profile.update') }}" class="mt-6 space-y-5">@csrf @method('PATCH')
    <div><label for="name" class="font-semibold">Name</label><input id="name" name="name" value="{{ old('name', $user->name) }}" required class="mt-2 w-full rounded-lg border border-slate-300 p-3">@error('name')<p class="text-sm text-red-700">{{ $message }}</p>@enderror</div>
    <div><label for="email" class="font-semibold">Email</label><input id="email" value="{{ $user->email }}" readonly class="mt-2 w-full rounded-lg border border-slate-300 bg-slate-100 p-3"><p class="mt-1 text-xs text-slate-500">Email changes are deferred until re-verification is implemented.</p></div>
    <div><label for="mobile_number" class="font-semibold">Phone</label><input id="mobile_number" name="mobile_number" value="{{ old('mobile_number', $user->mobile_number) }}" class="mt-2 w-full rounded-lg border border-slate-300 p-3"></div>
    <div><label for="preferred_language" class="font-semibold">Preferred language</label><select id="preferred_language" name="preferred_language" class="mt-2 w-full rounded-lg border border-slate-300 p-3"><option value="">Site default</option>@foreach(['hi'=>'Hindi','pa'=>'Punjabi','en'=>'English'] as $value=>$label)<option value="{{ $value }}" @selected(old('preferred_language',$user->preferred_language)===$value)>{{ $label }}</option>@endforeach</select></div>
    <button class="rounded-lg bg-red-700 px-5 py-3 font-bold text-white">Save profile</button>
</form>
@endsection
