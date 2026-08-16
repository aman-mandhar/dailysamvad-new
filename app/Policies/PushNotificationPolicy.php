<?php

namespace App\Policies;

use App\Models\PushNotification;
use App\Models\User;

class PushNotificationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_active ? null : false;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view push notifications');
    }

    public function view(User $user, PushNotification $notification): bool
    {
        return $user->hasPermissionTo('view push notifications');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create push notifications');
    }

    public function update(User $user, PushNotification $notification): bool
    {
        return $notification->isDraft() && $user->hasPermissionTo('update push notifications');
    }

    public function delete(User $user, PushNotification $notification): bool
    {
        return $notification->isDraft() && $user->hasPermissionTo('delete push notifications');
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function viewAnalytics(User $user, PushNotification $notification): bool
    {
        return $user->hasPermissionTo('view push analytics');
    }

    public function send(User $user, PushNotification $notification): bool
    {
        return $notification->isDraft() && $user->hasPermissionTo('send push notifications');
    }
}
