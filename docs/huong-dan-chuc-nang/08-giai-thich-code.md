# Giải thích code từng phần quan trọng

Không giải thích từng dấu ngoặc. Tập trung logic dễ hỏi khi bảo vệ đồ án.

---

## File: `app/Services/Billing/BillingService.php`

**Vai trò:** tạo yêu cầu thanh toán, xác nhận, mô phỏng, gia hạn gói. Controller không tự `update` hạn công ty.

### `createRequest`

```php
'amount' => $plan->price_amount,
```

`$plan` là model lấy từ DB theo id. Dù form POST `amount=1`, số ghi vào `payments` vẫn là giá gói. Bỏ dòng này rồi lấy `$request->amount` là lỗ hổng (user tự giảm giá).

Nếu đã có `pending`, trả về giao dịch cũ — chống bấm đúp tạo nhiều yêu cầu.

`??` không dùng ở đây; `config('billing.currency', 'VND')` nghĩa là: đọc config, không có thì `'VND'`.

### `markPaid`

```php
return DB::transaction(function () use ($payment, $providerTxnId) {
    $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
```

- `DB::transaction`: nhiều câu SQL thành một khối. Lỗi → `rollback`, không để payment `paid` mà công ty chưa có hạn.
- `lockForUpdate()`: hai request cùng lúc không cùng gia hạn.
- Nếu `$locked->status === Paid` → `return $locked` ngay, **không** `addDays` lần nữa.

```php
$starts = $company->hasActiveSubscription() ? $company->subscription_ends_at : now();
$ends = $starts->copy()->addDays($plan->duration_days);
```

Còn hạn thì cộng thêm từ ngày hết hạn cũ (gia hạn). Hết hạn thì tính từ `now()`.

`UnprocessableEntityHttpException` → HTTP 422. Không phải `die($e)` — user không thấy stack trace.

---

## File: `app/Models/JobPost.php` — scope `open`

```php
$query->where('status', JobStatus::Open)
    ->whereHas('company', function (Builder $company): void {
        $company->whereNotNull('subscription_ends_at')
            ->where('subscription_ends_at', '>', now());
    });
```

`JobPost::open()` dùng ở trang sinh viên. Tin `open` nhưng công ty hết hạn **không** hiện. `whereHas` = EXISTS subquery, Eloquent bind ngày `now()` — không nối chuỗi SQL.

Nếu bỏ `whereHas`, tin hết hạn vẫn hiện, thanh toán mất ý nghĩa.

---

## File: `app/Services/Review/CompanyReviewService.php`

`eligibleApplication` trả `Application|null`. Dấu `?` trước kiểu = có thể null. `??` khác: `$a ?? $b` lấy `$b` khi `$a` là null.

`create` kiểm tra rating, đơn hired, trùng. `DB::transaction` vừa insert review vừa cập nhật `companies.rating`. `forceFill` ghi `rating` / `reviews_count` dù hai cột không nằm trong `$fillable` (tránh mass-assignment từ form).

`UniqueConstraintViolationException`: hai tab gửi cùng lúc, unique index chặn dòng thứ hai.

`ConflictHttpException` → HTTP 409.

---

## File: `app/Policies/RecruiterNotePolicy.php`

```php
private function owns(User $user, RecruiterNote $note): bool
{
    return $user->hasRole(Role::Employer) && $user->employer?->id === $note->employer_id;
}
```

`?->` (nullsafe): nếu user không có hồ sơ employer thì không lỗi, trả null rồi so sánh false.

Không so sánh `company_id` khi sửa: HR cùng công ty vẫn **không** sửa ghi chú của đồng nghiệp. Đúng yêu cầu “A không xem ghi chú của B”.

`Gate::authorize('update', $note)` gọi policy này. Fail → 403, không tới `update()` của service.

---

## File: `app/Services/Notification/UserNotificationService.php`

```php
$existing = UserNotification::query()->where('dedup_key', $dedupKey)->first();
if ($existing) {
    return $existing;
}
```

`return` kết thúc hàm, không insert. Unique DB là lớp thứ hai nếu hai process xen kẽ.

`read_at === null` = chưa đọc. `markAllRead` dùng `update` theo `user_id` đang đăng nhập, không nhận danh sách id từ client.

---

## File: `routes/web.php` (nhóm mới)

`Route::middleware(['auth', 'role:employer'])` bọc billing + notes.

`{company:slug}` = implicit binding theo cột `slug`, không phải `id`.

GET `/support/create` **đặt trước** `/support/{ticket}` — nếu đảo, chữ `create` bị hiểu là id ticket.

---

## Cú pháp PHP hay gặp

| Cú pháp | Ý nghĩa |
|---|---|
| `enum Role: string` | Tập giá trị cố định, lưu dạng chuỗi |
| `function f(): View` | Kiểu trả về bắt buộc |
| `$user?->student` | nullsafe |
| `auth()->user()` | user session, không phải `$_SESSION` tự đọc |
| `$request->validated()` | chỉ field đã qua rules |
| `old('body')` | giá trị form lần gửi trước (khi lỗi) |
| `{{ $x }}` | escape HTML |
| `@csrf` | token chống CSRF |

Laravel **không** dùng `$_GET`/`$_POST` trực tiếp trong code mới. `$request->query()` / `$request->input()` tương đương an toàn hơn.
