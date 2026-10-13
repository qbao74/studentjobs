<?php

namespace App\Enums;

enum NotificationType: string
{
    case ApplicationStatusChanged = 'application.status';
    case ApplicationReceived = 'application.received';
    case PaymentUpdated = 'payment.updated';
    case SupportReplied = 'support.replied';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationStatusChanged => 'Trạng thái đơn ứng tuyển',
            self::ApplicationReceived => 'Đơn ứng tuyển mới',
            self::PaymentUpdated => 'Thanh toán',
            self::SupportReplied => 'Hỗ trợ khách hàng',
        };
    }
}
