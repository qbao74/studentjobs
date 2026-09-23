<?php

namespace App\Enums;

enum JobStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Đang tuyển',
            self::Closed => 'Đã đóng',
            self::Hidden => 'Bị ẩn',
        };
    }
}
