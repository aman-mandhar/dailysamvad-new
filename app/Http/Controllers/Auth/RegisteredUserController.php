<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Auth\DashboardRedirector;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, DashboardRedirector $redirector): RedirectResponse
    {
        $data = $request->validated();
        $referrerId = User::query()
            ->where('refcode', Str::upper((string) ($data['referral_code'] ?? '')))
            ->value('id');

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
            'is_active' => true,
            'is_public' => false,
        ]);
        $user->ref_id = $referrerId;
        $user->save();
        $user->assignRole('subscriber');

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($redirector->routeFor($user));
    }
}
