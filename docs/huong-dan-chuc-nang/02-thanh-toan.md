# Thanh toán nhà tuyển dụng

## 1. Dùng để làm gì

Nhà tuyển dụng mua **gói duy trì tin theo tháng**. Tin `open` chỉ hiện với sinh viên khi công ty còn hạn (`companies.subscription_ends_at > now()`).

Đây là **chế độ mô phỏng đồ án** (`BILLING_DRIVER=simulation`). Giao diện ghi rõ không phải cổng ngân hàng thật. Gói chỉ kích hoạt khi giao dịch được xác nhận thành công (nút mô phỏng thành công hoặc admin xác nhận). Không tự coi “đã thanh toán” khi mới tạo yêu cầu.

## 2. Ai được dùng

- Nhà tuyển dụng: tạo yêu cầu, xem lịch sử, mô phỏng kết quả của **công ty mình**.
- Admin: xem mọi giao dịch, xác nhận / đánh dấu thất bại.
- Sinh viên: không vào `/employer/billing` (403).

## 3. Thao tác trên giao diện

1. Đăng nhập HR → menu **Thanh toán**.
2. Xem hạn gói, danh sách tin (đang hoạt động / sắp hết hạn / đã hết hạn).
3. Chọn gói → **Tạo yêu cầu thanh toán**.
4. Trang chi tiết hiện mã `PAY-...`, số tiền lấy từ bảng `service_plans`.
5. Bấm **Mô phỏng thanh toán thành công** hoặc **thất bại**. Admin có thể xác nhận hộ.

## 4. Request đi đâu

| Thao tác | Method | Route name | Controller |
|---|---|---|---|
| Danh sách | GET `/employer/billing` | `employer.billing.index` | `Employer\BillingController@index` |
| Tạo yêu cầu | POST `/employer/billing` | `employer.billing.store` | `store` |
| Chi tiết | GET `/employer/billing/{payment}` | `employer.billing.show` | `show` |
| Mô phỏng | POST `/employer/billing/{payment}/simulate` | `employer.billing.simulate` | `simulate` |
| Admin xác nhận | PATCH `/admin/payments/{payment}` | `admin.payments.update` | `Admin\PaymentController@update` |

## 5. Dữ liệu được kiểm tra thế nào

- Form chỉ gửi `service_plan_id`. **Không tin `amount` từ trình duyệt.**
- `BillingService::createRequest` đọc `price_amount` từ `service_plans`.
- Policy `PaymentPolicy`: chỉ HR cùng `company_id` hoặc admin được xem.
- Gửi lại cùng `provider_txn_id` / giao dịch đã `paid`: **không cộng thêm ngày**.

## 6. Database đổi khi nào

- Tạo yêu cầu → insert `payments` status `pending`.
- Thành công (trong transaction + `lockForUpdate`):
  - `payments.status = paid`, ghi `paid_at`, `starts_on`, `ends_on`.
  - Cập nhật `companies.subscription_ends_at` (cộng dồn nếu còn hạn).
- Thất bại → `failed`, không đụng hạn gói.
- Hết hạn yêu cầu (quá `expires_at`) → `expired`.

## 7. Lỗi thường gặp

- Còn một giao dịch `pending` → hệ thống mở lại giao dịch đó, không tạo thêm.
- Mô phỏng khi đã `paid` → giữ nguyên hạn, không gia hạn lần hai.
- Đăng tin khi hết hạn gói → lỗi “Hãy thanh toán trước khi đăng tin đang mở.”

## 8. Bảo mật

- Không lưu số thẻ.
- CSRF trên mọi POST/PATCH.
- Số tiền server-side.
- Idempotent: unique `reference`, unique `provider_txn_id`.
