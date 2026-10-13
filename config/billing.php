<?php

/**
 * Cấu hình gói duy trì tin tuyển dụng theo tháng.
 * Giá và thời hạn chỉnh ở đây rồi chạy seeder / cập nhật bảng service_plans.
 * Controller không tin số tiền gửi từ trình duyệt — luôn lấy từ bảng service_plans.
 */
return [

    /*
    | driver = simulation: đồ án, không thu tiền thật.
    | Không mô tả đây là cổng thanh toán ngân hàng thật.
    */
    'driver' => env('BILLING_DRIVER', 'simulation'),

    'currency' => 'VND',

    /** Yêu cầu thanh toán pending quá giờ này thì chuyển sang expired. */
    'payment_expires_hours' => (int) env('BILLING_PAYMENT_EXPIRES_HOURS', 24),

    /** Còn bao nhiêu ngày thì hiện "sắp hết hạn". */
    'expiring_soon_days' => (int) env('BILLING_EXPIRING_SOON_DAYS', 7),

    /**
     * Gói mặc định khi chạy migration/seeder lần đầu.
     * max_open_jobs = null nghĩa là không giới hạn số tin đang mở.
     *
     * @var list<array{code: string, name: string, description: string, price_amount: int, duration_days: int, max_open_jobs: int|null}>
     */
    'plans' => [
        [
            'code' => 'basic',
            'name' => 'Cơ bản',
            'description' => 'Duy trì tối đa 2 tin đang tuyển trong 30 ngày.',
            'price_amount' => 99000,
            'duration_days' => 30,
            'max_open_jobs' => 2,
        ],
        [
            'code' => 'standard',
            'name' => 'Tiêu chuẩn',
            'description' => 'Duy trì tối đa 8 tin đang tuyển trong 30 ngày.',
            'price_amount' => 199000,
            'duration_days' => 30,
            'max_open_jobs' => 8,
        ],
        [
            'code' => 'premium',
            'name' => 'Nâng cao',
            'description' => 'Không giới hạn số tin đang tuyển trong 30 ngày.',
            'price_amount' => 399000,
            'duration_days' => 30,
            'max_open_jobs' => null,
        ],
    ],
];
