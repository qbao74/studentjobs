<?php

namespace App\Enums;

enum SupportCategory: string
{
    case Product = 'product';
    case Payment = 'payment';
    case Account = 'account';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Product => 'Sản phẩm / tin tuyển dụng',
            self::Payment => 'Thanh toán / gói dịch vụ',
            self::Account => 'Tài khoản',
            self::Other => 'Khác',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
