<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserNotification;

class UserNotificationPolicy
{
    public function view(User $user, UserNotification $notification): bool
    {
        return $user->id === $notification->user_id;
    }

    public function update(User $user, UserNotification $notification): bool
    {
        return $user->id === $notification->user_id;
    }
}
