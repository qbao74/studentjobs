# Database

Migration: `database/migrations/2026_10_13_072950_create_billing_reviews_notes_notifications_and_support_tables.php`.

Chạy: `php artisan migrate` (an toàn, chỉ thêm). Rollback: `php artisan migrate:rollback --step=1` (xóa bảng mới, **không** xóa users/jobs cũ).

Sao lưu trước: `cp database/database.sqlite database/database.sqlite.bak`

## Bảng tái sử dụng

- `users`, `students`, `employers`, `companies`, `job_posts`, `applications` — không đổi khóa chính.
- `companies` **thêm** `service_plan_id`, `subscription_starts_at`, `subscription_ends_at`.
- `applications.note` giữ nguyên, không dùng cho ghi chú mới.

Công ty cũ chưa có hạn gói được gán gói Tiêu chuẩn 30 ngày để tin demo không biến mất.

## Bảng mới

### service_plans

Gói bán. PK `id`. Unique `code`. `price_amount` integer (VND, tránh float). `max_open_jobs` null = không giới hạn.

### payments

Lịch sử giao dịch. FK `company_id`, `user_id`, `service_plan_id` (restrict — không xóa gói đang có giao dịch). Unique `reference`, unique `provider_txn_id` (idempotent). Status: pending/paid/failed/expired.

### company_reviews

1-n: company có nhiều reviews; student có nhiều reviews. Unique `(student_id, company_id)`. FK `application_id` để truy vết đơn hired. Xóa student/company → cascade.

### recruiter_notes

N-1 tới `applications` và `employers`. Không unique — một HR ghi nhiều ghi chú. Cascade khi xóa đơn hoặc HR.

### user_notifications

FK `user_id`. Unique `dedup_key`. `related_type` + `related_id` morph (trỏ Application/Payment/SupportTicket). Index `(user_id, read_at)` phục vụ đếm chưa đọc.

### support_tickets / support_replies

Ticket 1-n replies. Cascade xóa ticket thì mất replies.

## Giá gói

Nằm ở `config/billing.php` và được copy vào `service_plans` lúc migrate. Muốn đổi giá: sửa config **và** cập nhật dòng trong `service_plans`. Controller không đọc giá từ form.
