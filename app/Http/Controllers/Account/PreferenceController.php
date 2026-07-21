<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdatePreferenceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreferenceController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.preferences', ['user' => $request->user()]);
    }

    public function update(UpdatePreferenceRequest $request): RedirectResponse
    {
        $request->user()->update(['preferred_language' => $request->validated('preferred_language')]);

        return back()->with('success', 'Preference updated.');
    }
}
