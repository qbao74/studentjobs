# Hỗ trợ khách hàng

## 1. Mục tiêu

Sinh viên và nhà tuyển dụng gửi câu hỏi (sản phẩm, thanh toán, tài khoản). Admin phản hồi. Không có role “nhân viên hỗ trợ” mới.

## 2. Trạng thái

`open` → `in_progress` (user nhắn thêm) / `answered` (admin trả lời) → `closed`.

## 3. Phân quyền

- User chỉ xem ticket `user_id` của mình.
- Admin xem tất cả tại `/admin/support`.
- Form có thêm `role=admin` cũng **không** biến user thành admin — role nằm ở cột `users.role`, middleware `role:admin`.

## 4. Luồng

Tạo ticket: insert `support_tickets` + một `support_replies` (nội dung ban đầu).

Admin trả lời: thêm reply, status = `answered`, tạo thông báo cho user.

User đóng: PATCH `support.close`.
