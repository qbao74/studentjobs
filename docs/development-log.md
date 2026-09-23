# Nhật ký phát triển Jobly

File này là giáo trình đi kèm `git log`. Mỗi commit có một mục: vì sao làm, đã làm gì, request đi qua những file nào, và cần nhớ điều gì.

Cách đọc:

- `git log --oneline` để xem thứ tự các bước.
- `git show <hash>` để xem đúng phần code của một bước.
- `git checkout <hash>` để mở lại project ở đúng thời điểm đó, rồi `git checkout feature/hoan-thien` để quay về.

Mã hash của các commit trong một phase được điền bằng một commit `docs:` ở cuối phase đó, vì một commit không tự biết hash của chính nó.

## Những gì đã có trước nhật ký này

Các commit dưới đây làm trước khi có quy tắc commit mới, nên message không có tiền tố `feat:`.

| Giai đoạn | Commit | Kết quả |
|---|---|---|
| Giao diện | `813103c` → `79ea36b` | Các trang sinh viên chạy trong Laravel bằng `Route::view`, dữ liệu giả trong `public/jobly/js/data.js` |
| Database | `012e657` → `2f268d3` | Migration cho công ty, sinh viên, tin tuyển, kỹ năng, CV, đơn, kết quả khớp; seeder copy từ `data.js` |
| Phân quyền | `8e329e0`, `833a589` | Middleware `EnsureRole` và alias `role` trong `bootstrap/app.php` |
| Đăng nhập | `376ac36`, `99ee5b7` | `LoginController` mở form, nhận form, đăng xuất |
| Đăng ký | `48fab0b`, `3893f3a` | `RegisterController` tạo `users` + `students` trong một transaction |

---

# PHASE 00 — Chuẩn bị

## Commit: 82233f4

### Tiêu đề
chore: Cài thư viện đọc PDF và Laravel Boost

### Ngày
2026-09-23

### Mục đích
Phần xử lý CV phải đọc được chữ trong file PDF. PHP không tự làm được, nên cần thư viện `smalot/pdfparser`. Laravel Boost sinh bộ hướng dẫn code đúng với phiên bản Laravel đang dùng.

### Đã làm
- Thêm `smalot/pdfparser` vào `require` (chạy cả khi deploy).
- Thêm `laravel/boost` vào `require-dev` (chỉ dùng lúc phát triển).
- Boost sinh nội dung cho `CLAUDE.md`; `AGENTS.md` được chép giống hệt để hai file không lệch nhau.

### Luồng code
`composer require` → ghi `composer.json` → khóa đúng phiên bản trong `composer.lock` → tải code vào `vendor/`.

### File quan trọng
- `composer.json`, `composer.lock`
- `CLAUDE.md`, `AGENTS.md`, `boost.json`

### Kiến thức cần nhớ
- `composer.json` ghi khoảng phiên bản cho phép (`^2.12`). `composer.lock` ghi đúng phiên bản đã cài, để máy khác chạy `composer install` ra cùng code.
- `require-dev` không được cài khi deploy với `--no-dev`.
- `vendor/` không commit. Người khác clone về phải chạy `composer install`.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- `composer.json`, rồi chạy `composer update <tên-gói>`.
- Commit cả `composer.lock` sau khi đổi.

## Commit: _(điền ở cuối phase)_

### Tiêu đề
docs: Tạo nhật ký phát triển và ghi lại các phase đã làm trước đó

### Ngày
2026-09-23

### Mục đích
Biến `git log` thành giáo trình: mỗi bước có phần giải thích đi kèm.

### Đã làm
- Tạo `docs/development-log.md`.
- Tóm tắt các giai đoạn đã làm trước đó theo commit cũ.

### Luồng code
Không có code chạy. Đây là tài liệu.

### File quan trọng
- `docs/development-log.md`

### Kiến thức cần nhớ
- Commit `docs:` chỉ đổi tài liệu, không đổi hành vi app.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Mục của commit liên quan trong file này.

<!-- mục-tiếp-theo -->
