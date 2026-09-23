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

    /** Vị trí trên thanh tiến trình 5 bước: Ứng tuyển → Đã xem → Shortlist → Phỏng vấn → Kết quả. */
    public function step(): int
    {
        return match ($this) {
            self::Pending => 1,
            self::Viewed => 2,
            self::Shortlisted => 3,
            self::Interview => 4,
            self::Hired, self::Rejected => 5,
        };
    }

    /** Đơn đã có kết quả cuối thì không đổi trạng thái được nữa. */
    public function isFinal(): bool
    {
        return in_array($this, [self::Hired, self::Rejected], true);
    }

    /**
     * Các bước cho trang tiến trình. status: done | current | upcoming | rejected.
     *
     * @return list<array{key: string, label: string, status: string}>
     */
    public function timeline(): array
    {
        $steps = [
            'applied' => 'Đã ứng tuyển',
            'viewed' => 'Nhà tuyển dụng đã xem',
            'shortlist' => 'Vào vòng trong',
            'interview' => 'Phỏng vấn',
            'result' => match ($this) {
                self::Hired => 'Trúng tuyển',
                self::Rejected => 'Chưa phù hợp',
                default => 'Kết quả',
            },
        ];

        $current = $this->step();
        $result = [];
        $i = 1;

        foreach ($steps as $key => $label) {
            $status = match (true) {
                $i < $current => 'done',
                $i > $current => 'upcoming',
                $this === self::Hired => 'done',
                $this === self::Rejected => 'rejected',
                default => 'current',
            };
            $result[] = ['key' => $key, 'label' => $label, 'status' => $status];
            $i++;
        }

        return $result;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
