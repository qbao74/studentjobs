# Đánh giá công ty

## 1. Mục tiêu

Sinh viên **đã trúng tuyển** (`applications.status = hired`) mới được gửi điểm 1–5 và nhận xét. Điểm trung bình **tính từ bảng `company_reviews`**, không tin số gửi từ client.

## 2. Ai được dùng

- Khách / sinh viên: xem danh sách đánh giá (`GET /companies/{slug}/reviews`).
- Sinh viên đủ điều kiện: gửi đánh giá.
- Nhà tuyển dụng: **không** sửa/xóa đánh giá của sinh viên (`CompanyReviewPolicy`).

## 3. Thao tác

- Menu **Đánh giá** hoặc nút trên đơn đã trúng tuyển.
- Trang công ty có link “x/5 · n đánh giá”.
- Mỗi cặp (sinh viên, công ty) chỉ một dòng — unique trong database.

## 4. Luồng

```mermaid
flowchart TD
  A[POST /companies/{slug}/reviews] --> B[StoreCompanyReviewRequest]
  B --> C[CompanyReviewController@store]
  C --> D[CompanyReviewService::create]
  D --> E{Có đơn hired của đúng công ty?}
  E -->|Không| F[422 / lỗi form]
  E -->|Có| G{Đã đánh giá chưa?}
  G -->|Rồi| F
  G -->|Chưa| H[Insert company_reviews]
  H --> I[Tính lại companies.rating]
```

File nhận request: `app/Http/Controllers/CompanyReviewController.php`, hàm `store`.

Service: `app/Services/Review/CompanyReviewService.php`.

Điều kiện **không** lấy từ hidden input `hired=1`. Service tự query:

```php
$student->applications()
    ->where('status', ApplicationStatus::Hired)
    ->whereHas('jobPost', fn ($q) => $q->where('company_id', $company->id))
```

## 5. Ví dụ dữ liệu

Trước: sinh viên id=3, công ty id=4, đơn status=`interview` → gửi đánh giá → bị từ chối, 0 dòng `company_reviews`.

Sau khi HR đổi đơn thành `hired`: POST rating=5, comment đủ 10 ký tự → 1 dòng review, `companies.rating = 5.0`, `reviews_count = 1`.

Gửi lần hai → unique `(student_id, company_id)` + kiểm tra PHP → lỗi “Bạn đã đánh giá công ty này rồi.”
