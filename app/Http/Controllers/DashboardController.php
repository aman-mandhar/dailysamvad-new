<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'roleLabel' => $user->getRoleNames()
                ->map(fn (string $role): string => str($role)->replace('-', ' ')->title()->toString())
                ->join(', ') ?: 'No assigned role',
            'referralLink' => route('register', ['ref' => $user->refcode]),
            'referralCount' => $user->subscriberReferrals()->count(),
            'bookmarkCount' => $user->bookmarks()->whereHas('post', fn ($query) => $query->published())->count(),
            'historyCount' => $user->postVisits()->whereHas('post', fn ($query) => $query->published())->distinct('post_id')->count('post_id'),
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}
