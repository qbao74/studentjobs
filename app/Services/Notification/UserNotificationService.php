<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;

class UserNotificationService
{
    /**
     * Tạo thông báo nếu chưa có cùng dedup_key (chống gửi lại / xử lý trùng sự kiện).
     *
     * @param  array{title: string, body: string, link?: ?string}  $payload
     */
    public function notify(User $user, NotificationType $type, string $dedupKey, array $payload, ?Model $related = null): UserNotification
    {
        $existing = UserNotification::query()->where('dedup_key', $dedupKey)->first();
        if ($existing) {
            return $existing;
        }

        try {
            return UserNotification::query()->create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $payload['title'],
                'body' => $payload['body'],
                'link' => $payload['link'] ?? null,
                'dedup_key' => $dedupKey,
                'related_type' => $related?->getMorphClass(),
                'related_id' => $related?->getKey(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return UserNotification::query()->where('dedup_key', $dedupKey)->firstOrFail();
        }
    }

    public function unreadCount(User $user): int
    {
        return $user->userNotifications()->unread()->count();
    }

    public function markRead(UserNotification $notification): void
    {
        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }
    }

    public function markAllRead(User $user): int
    {
        return $user->userNotifications()->unread()->update(['read_at' => now()]);
    }
}
