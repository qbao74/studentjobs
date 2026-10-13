<?php

namespace App\Services\Support;

use App\Enums\NotificationType;
use App\Enums\Role;
use App\Enums\SupportCategory;
use App\Enums\SupportTicketStatus;
use App\Models\SupportReply;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Notification\UserNotificationService;
use Illuminate\Support\Facades\DB;

class SupportTicketService
{
    public function __construct(private UserNotificationService $notifications) {}

    public function open(User $user, SupportCategory $category, string $subject, string $body): SupportTicket
    {
        return DB::transaction(function () use ($user, $category, $subject, $body) {
            $ticket = SupportTicket::query()->create([
                'user_id' => $user->id,
                'category' => $category,
                'subject' => $subject,
                'status' => SupportTicketStatus::Open,
            ]);

            $this->addReply($ticket, $user, $body, notify: false);

            return $ticket->fresh(['replies.user']);
        });
    }

    public function reply(SupportTicket $ticket, User $author, string $body): SupportReply
    {
        return DB::transaction(function () use ($ticket, $author, $body) {
            $reply = $this->addReply($ticket, $author, $body, notify: true);

            $ticket->update([
                'status' => $author->hasRole(Role::Admin)
                    ? SupportTicketStatus::Answered
                    : SupportTicketStatus::InProgress,
            ]);

            return $reply;
        });
    }

    public function close(SupportTicket $ticket): void
    {
        $ticket->update(['status' => SupportTicketStatus::Closed]);
    }

    public function setStatus(SupportTicket $ticket, SupportTicketStatus $status): void
    {
        $ticket->update(['status' => $status]);
    }

    private function addReply(SupportTicket $ticket, User $author, string $body, bool $notify): SupportReply
    {
        $reply = $ticket->replies()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);

        if ($notify) {
            $this->notifyCounterpart($ticket, $author, $reply);
        }

        return $reply;
    }

    private function notifyCounterpart(SupportTicket $ticket, User $author, SupportReply $reply): void
    {
        $ticket->loadMissing('user');
        $isStaff = $author->hasRole(Role::Admin);
        $recipient = $isStaff ? $ticket->user : User::query()->where('role', Role::Admin)->where('is_active', true)->first();

        if ($recipient === null || $recipient->is($author)) {
            return;
        }

        $this->notifications->notify(
            $recipient,
            NotificationType::SupportReplied,
            'support:'.$ticket->id.':reply:'.$reply->id,
            [
                'title' => $isStaff ? 'Yêu cầu hỗ trợ đã có phản hồi' : 'Yêu cầu hỗ trợ mới từ người dùng',
                'body' => $ticket->subject,
                'link' => $isStaff
                    ? route('support.show', $ticket)
                    : route('admin.support.show', $ticket),
            ],
            $ticket,
        );
    }
}
