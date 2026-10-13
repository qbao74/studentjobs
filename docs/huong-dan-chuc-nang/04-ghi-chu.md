# Ghi chú nội bộ nhà tuyển dụng

## 1. Khác tin nhắn thế nào

- **Tin nhắn** (`messages`): hai bên đơn đều đọc được.
- **Ghi chú** (`recruiter_notes`): chỉ **đúng HR đã viết** được xem/sửa/xóa.
- Cột cũ `applications.note` vẫn còn trong database nhưng **không dùng** cho chức năng này (một ô text, không có lịch sử, không phân quyền theo người viết).

## 2. Ai được dùng

- HR của đúng công ty đăng tin: tạo ghi chú trên đơn.
- Chỉ `employer_id` của người viết mới `update`/`delete`.
- HR công ty khác, sinh viên, khách: 403. Ẩn giao diện thôi là không đủ — mỗi POST/PATCH/DELETE đều `Gate::authorize`.

## 3. Giao diện

Mở đơn ứng tuyển (`/employer/applications/{id}`), kéo xuống khối **Ghi chú nội bộ**.

Nếu chưa có ghi chú: “Chưa có ghi chú cho ứng viên này.”

## 4. Request

| Thao tác | Route | Policy |
|---|---|---|
| Thêm | POST `employer.notes.store` | `create` + đơn thuộc công ty mình |
| Sửa | PATCH `employer.notes.update` | `update` + `employer_id` = HR hiện tại |
| Xóa | DELETE `employer.notes.destroy` | `delete` như trên |

`employer_id` **không** lấy từ form. Controller lấy `$request->user()->employer`.

## 5. XSS

View in `{{ $note->body }}` — Blade escape. Nếu viết `{!! $note->body !!}` thì script trong ghi chú có thể chạy.
