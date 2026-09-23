<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Pending = 'pending';
    case Viewed = 'viewed';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Hired = 'hired';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Đã gửi',
            self::Viewed => 'Đã xem',
            self::Shortlisted => 'Vào vòng trong',
            self::Interview => 'Phỏng vấn',
            self::Hired => 'Trúng tuyển',
            self::Rejected => 'Chưa phù hợp',
        };
    }

    /** Vị trí trên thanh tiến trình 4 bước: Đã gửi → Đã xem → Phỏng vấn → Kết quả. */
    public function step(): int
    {
        return match ($this) {
            self::Pending => 1,
            self::Viewed, self::Shortlisted => 2,
            self::Interview => 3,
            self::Hired, self::Rejected => 4,
        };
    }

    /** Đơn đã có kết quả cuối thì không đổi trạng thái được nữa. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Hired, self::Rejected], true);
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
