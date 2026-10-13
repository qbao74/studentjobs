# Bài tập tự thực hành

Làm trên branch riêng hoặc copy file. Đừng commit nếu chưa được yêu cầu.

---

## Bài 1 — Đổi số ngày “sắp hết hạn”

**Mục tiêu:** biết config khác hardcode.

**Mở:** `config/billing.php`, khóa `expiring_soon_days`.

**Yêu cầu:** đổi 7 thành 14. Tìm chỗ đọc config trong `Company::isSubscriptionExpiringSoon()`.

**Kỳ vọng:** tin còn 10 ngày hiện “Sắp hết hạn”.

**Tự kiểm:** tạo công ty `subscription_ends_at = now()->addDays(10)`, mở `/employer/billing`.

**Lỗi hay gặp:** sửa view nhưng quên config; hoặc cache config (`php artisan config:clear`).

---

## Bài 2 — Cho phép đánh giá từ 1–10

**Mở:** `app/Models/CompanyReview.php` (`MIN_RATING`/`MAX_RATING`), `StoreCompanyReviewRequest`, view `reviews/create.blade.php`.

**Yêu cầu:** nới thang điểm. `refreshCompanyStats` vẫn `avg(rating)`.

**Kỳ vọng:** gửi 10 thì lưu 10; gửi 11 vẫn lỗi validation.

**Tự kiểm:** `php artisan test --compact tests/Feature/Review/CompanyReviewTest.php` — sẽ fail test rating=9, hãy sửa test cho khớp thang mới.

---

## Bài 3 — Thông báo khi đơn chuyển sang `viewed`

**Mở:** `Employer/ApplicationController.php` hàm `show` (đổi pending → viewed) và `updateStatus`.

**Gợi ý:** `UserNotificationService::notify` với `dedup_key` dạng `application:{id}:status:viewed`.

**Kỳ vọng:** sinh viên thấy thông báo “Nhà tuyển dụng đã xem”. Mở đơn hai lần không tạo hai thông báo.

**Tự kiểm:** feature test bắt chước `UserNotificationTest`.

---

## Bài 4 — HR cùng công ty được đọc ghi chú chung (tùy chọn)

Hiện tại ghi chú **riêng từng người**. Nếu giảng viên muốn share trong công ty:

**Mở:** `RecruiterNotePolicy::owns` / `view`, và query trong `ApplicationController@show` (đang `where employer_id = tôi`).

**Cẩn thận:** sửa `view` nhưng quên `update` thì người khác sửa được ghi chú của bạn.

---

## Đáp án tham khảo (xem sau khi đã tự làm)

### Bài 1

Chỉ cần `expiring_soon_days => 14`. `Company::isSubscriptionExpiringSoon` đã đọc `config('billing.expiring_soon_days', 7)`.

### Bài 2

```php
public const MAX_RATING = 10;
```

Request: `'max:'.CompanyReview::MAX_RATING`. View: `@for ($n = 10; $n >= 1; $n--)`.

### Bài 3

Trong `show`, sau `update(['status' => Viewed])`, gọi `$this->notifications->notify(...)` giống `updateStatus`. Đưa `Viewed` vào danh sách trạng thái thông báo, hoặc gọi riêng một lần với dedup_key cố định.

### Bài 4

`view`: recruiter cùng `company_id`. `update`/`delete`: vẫn `employer_id` của tác giả. Query notes: bỏ `where employer_id`, load `employer.user` để hiện tên người viết.
