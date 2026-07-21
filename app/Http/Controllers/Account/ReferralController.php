<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('account.referrals', [
            'user' => $user,
            'referralLink' => route('register', ['ref' => $user->refcode]),
            'referrals' => $user->subscriberReferrals()->select(['id', 'name', 'created_at'])->latest()->paginate(15),
            'thisMonth' => $user->subscriberReferrals()->where('created_at', '>=', now()->startOfMonth())->count(),
        ]);
    }
}
