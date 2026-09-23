<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;

/** Hội thoại gắn với một đơn ứng tuyển, giữa sinh viên và nhà tuyển dụng của tin đó. */
class MessageService
{
    private const HISTORY_LIMIT = 200;

    /**
     * Lấy tin nhắn (cũ → mới) và đánh dấu đã đọc những tin người kia gửi.
     *
     * @return Collection<int, Message>
     */
    public function thread(Application $application, User $viewer, ?int $afterId = null): Collection
    {
        $application->messages()
            ->whereNull('read_at')
            ->where('user_id', '!=', $viewer->id)
            ->update(['read_at' => now()]);

        return $application->messages()
            ->when($afterId, fn ($q) => $q->where('id', '>', $afterId))
            ->latest('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->reverse()
            ->values();
    }

    public function send(Application $application, User $sender, string $body): Message
    {
        return $application->messages()->create([
            'user_id' => $sender->id,
            'body' => $body,
        ]);
    }
}
