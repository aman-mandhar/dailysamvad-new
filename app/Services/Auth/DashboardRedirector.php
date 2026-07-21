<?php

namespace App\Services\Auth;

use App\Models\User;

class DashboardRedirector
{
    public function routeFor(User $user): string
    {
        if ($user->is_active && $user->can('access admin panel')) {
            return route('filament.admin.pages.dashboard');
        }

        return route('dashboard');
    }
}
