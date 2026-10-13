# Hướng dẫn chức năng nhóm 5 — Jobly

Tài liệu này giải thích **code thật** vừa thêm vào project Laravel **Student Jobs (Jobly)**. Không phải lý thuyết chung, không mô tả chức năng chưa tồn tại.

Đọc theo thứ tự:

| File | Nội dung |
|---|---|
| [01-kien-truc.md](01-kien-truc.md) | Request → Route → Controller → Service → Model → View |
| [02-thanh-toan.md](02-thanh-toan.md) | Gói dịch vụ, mô phỏng thanh toán, gia hạn tin |
| [03-danh-gia.md](03-danh-gia.md) | Đánh giá công ty sau khi trúng tuyển |
| [04-ghi-chu.md](04-ghi-chu.md) | Ghi chú nội bộ của nhà tuyển dụng |
| [05-thong-bao.md](05-thong-bao.md) | Thông báo đơn, thanh toán, hỗ trợ |
| [06-ho-tro.md](06-ho-tro.md) | Yêu cầu hỗ trợ và phản hồi |
| [07-database.md](07-database.md) | Bảng mới, cột, khóa, cách migrate an toàn |
| [08-giai-thich-code.md](08-giai-thich-code.md) | Giải thích từng đoạn code quan trọng |
| [09-bai-tap.md](09-bai-tap.md) | Bài tập tự làm + đáp án tham khảo |

## Cách chạy nhanh

```bash
cp .env.example .env   # nếu chưa có
php artisan migrate    # thêm bảng mới, không xóa dữ liệu cũ
php artisan serve
```

Sao lưu SQLite trước khi migrate:

```bash
cp database/database.sqlite database/database.sqlite.bak
```

Tài khoản mẫu (mật khẩu `password`): `lebao@student.edu.vn`, `hr@may-creative.vn`, `admin@jobly.vn`.
