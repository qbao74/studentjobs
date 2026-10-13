<?php

namespace App\Enums;

enum SupportTicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Answered = 'answered';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Mới',
            self::InProgress => 'Đang xử lý',
            self::Answered => 'Đã phản hồi',
            self::Closed => 'Đã đóng',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Closed;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
