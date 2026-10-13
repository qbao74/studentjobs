# Thông báo

## 1. Khi nào hệ thống tạo thông báo

| Sự kiện | Người nhận | `dedup_key` |
|---|---|---|
| Sinh viên nộp đơn | HR công ty | `application:{id}:received:{userId}` |
| Đơn → shortlisted / interview / hired / rejected | Sinh viên | `application:{id}:status:{status}` |
| Thanh toán paid/failed | HR công ty | `payment:{id}:{status}` |
| Admin phản hồi hỗ trợ | Chủ ticket | `support:{id}:reply:{replyId}` |

`dedup_key` unique: xử lý lại cùng sự kiện **không** tạo dòng thứ hai. Đổi pending → viewed khi HR mở đơn **không** tạo thông báo (không nằm trong danh sách trạng thái quan trọng).

## 2. Giao diện

`/notifications` — sinh viên dùng layout app, HR/admin dùng layout panel.

- Chưa đọc: viền trái.
- Bấm một dòng → đánh dấu đã đọc rồi chuyển `link`.
- **Đánh dấu tất cả đã đọc** chỉ update `user_id` của người đang đăng nhập.

## 3. Bảo mật

`UserNotificationPolicy`: `user_id` của thông báo phải trùng `auth()->id()`. Không tin `user_id` trên query string.

GET `/api/notifications/unread` trả JSON `{ unread: n }` cho badge (có thể poll). Không dùng WebSocket vì project chưa có.

## 4. Ví dụ

HR đổi đơn 12 sang `hired` → một dòng cho sinh viên, `read_at = null`, `link = /applications`.

Sinh viên bấm thông báo → `read_at` có giá trị, trình duyệt tới `/applications`.

HR khác không đọc được thông báo này (403).
