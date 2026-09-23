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

## Commit: 31342e2

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

---

# PHASE 01 — Hoàn thiện database

## Commit: 570a068

### Tiêu đề
refactor: Xóa ba migration rỗng không tạo bảng nào

### Ngày
2026-09-23

### Mục đích
`users.php`, `categories.php`, `skils.php` được tạo nhầm, `up()` chỉ có `//`. Để lại sẽ làm người đọc tưởng có bảng `categories`.

### Đã làm
- Xóa ba file. Các bảng thật không đổi.

### Luồng code
`php artisan migrate` đọc thư mục `database/migrations` theo thứ tự tên file, chạy `up()` của file nào chưa có trong bảng `migrations`.

### File quan trọng
- `database/migrations/`

### Kiến thức cần nhớ
- Tên file có timestamp ở đầu để quyết định thứ tự chạy.
- Chỉ nên xóa migration khi app chưa chạy thật trên server. Khi đã deploy, muốn bỏ bảng thì tạo migration mới để drop.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- `php artisan migrate:status` xem migration nào đã chạy.

## Commit: 911dd9d

### Tiêu đề
fix: Bổ sung lệnh xóa bảng khi rollback cho hai bảng trung gian kỹ năng

### Ngày
2026-09-23

### Mục đích
`php artisan migrate:rollback` bị lỗi `no such table: main.job_posts`.

### Đã làm
- `down()` của `student_skill` và `job_skill` giờ gọi `Schema::dropIfExists(...)`.

### Luồng code
Rollback chạy `down()` theo thứ tự ngược: `job_skill` → `student_skill` → ... → `skills`.

### File quan trọng
- `database/migrations/2026_08_26_030703_students_skills.php`
- `database/migrations/2026_08_26_030714_job_skills.php`

### Kiến thức cần nhớ
Bug xuất hiện ở: `migrate:rollback` và `migrate:refresh`.
Nguyên nhân: `down()` rỗng nên `job_skill` không bị xóa. Nó vẫn giữ khóa ngoại trỏ tới `job_posts` (đã bị xóa) nên SQLite từ chối khi xóa tiếp `skills`.
Cách sửa: mỗi `Schema::create` trong `up()` phải có `Schema::dropIfExists` tương ứng trong `down()`.
Commit sửa: `911dd9d`.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Chạy `migrate:rollback` rồi `migrate` sau mỗi migration mới.

## Commit: b8516b8

### Tiêu đề
feat: Thêm trạng thái khóa tài khoản và trạng thái tin tuyển dụng

### Ngày
2026-09-23

### Mục đích
Admin cần khóa được tài khoản. Nhà tuyển dụng cần đóng hoặc ẩn tin. Nhà tuyển dụng cần ghi chú cho từng đơn.

### Đã làm
- `users.is_active` (mặc định `true`) + index `role` (admin lọc theo vai trò).
- `job_posts.status` (`open`, `closed`, `hidden`), `job_posts.is_remote` + index ghép `(status, created_at)`.
- `applications.note` + index `status`.

### Luồng code
Migration mới dùng `Schema::table` (sửa bảng có sẵn) thay vì sửa file migration cũ.

### File quan trọng
- `database/migrations/2026_09_23_000001_add_status_columns.php`

### Kiến thức cần nhớ
- Index ghép `(status, created_at)` phục vụ đúng câu hỏi hay gặp nhất: "tin đang mở, mới nhất trước".
- `down()` phải xóa index trước rồi mới xóa cột.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Enum `JobStatus` (Phase 02) phải khớp các giá trị của cột `status`.

## Commit: 14ad47f

### Tiêu đề
feat: Thêm ràng buộc không trùng cho kỹ năng, hồ sơ và bảng trung gian

### Ngày
2026-09-23

### Mục đích
Chặn dữ liệu trùng ngay ở database, kể cả khi code quên kiểm tra: một kỹ năng hai lần, một user hai hồ sơ, một sinh viên gắn cùng kỹ năng hai lần.

### Đã làm
- `skills.ten_skill` đổi thành `name` (unique), thêm `aliases` (json) để nhận diện kỹ năng trong CV.
- `students.user_id`, `employers.user_id` unique. `employers.position` (chức vụ).
- `student_skill`: unique `(student_id, skill_id)`, cột `source` (`manual` hoặc `cv`).
- `job_skill`: unique `(job_post_id, skill_id)`.
- Seeder đổi `ten_skill` thành `name` trong cùng commit để `db:seed` vẫn chạy.

### Luồng code
Insert trùng → database ném `UniqueConstraintViolationException` → code bắt lỗi hoặc dùng `firstOrCreate` / `syncWithoutDetaching`.

### File quan trọng
- `database/migrations/2026_09_23_000002_add_unique_constraints.php`
- `database/seeders/JobPlatformSeeder.php`

### Kiến thức cần nhớ
- Validation ở controller là lớp chặn thứ nhất. Unique ở database là lớp cuối, chống cả khi hai request đến cùng lúc.
- `renameColumn` phải tách ra một `Schema::table` riêng trước khi tạo index trên tên mới.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Mọi chỗ còn dùng `ten_skill`: `rg ten_skill`.

## Commit: b22e625

### Tiêu đề
feat: Mở rộng bảng CV và kết quả khớp để lưu dữ liệu phân tích

### Ngày
2026-09-23

### Mục đích
Pipeline AI cần tách rõ: dữ liệu thô (file), dữ liệu đã xử lý (chữ trích ra, kỹ năng tìm được), điểm từng tiêu chí và tổng điểm.

### Đã làm
- `cvs`: unique `student_id` (mỗi sinh viên một CV), `mime_type`, `size`, `parse_status`, `parse_error`.
- `job_recommendations`: `breakdown` (json điểm từng tiêu chí), index `(student_id, score)`.

### Luồng code
Tải CV → lưu file (`path`) → trích chữ (`extracted_text`) → tách dữ liệu (`parsed`) → `parse_status`. Chấm điểm → `score` + `breakdown` + `pros` / `cons` / `comment`.

### File quan trọng
- `database/migrations/2026_09_23_000003_extend_cvs_and_recommendations.php`

### Kiến thức cần nhớ
- `parse_status = empty` nghĩa là file đọc được nhưng không có chữ (CV dạng ảnh). Khi đó hệ thống dùng kỹ năng sinh viên tự chọn.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- `app/Services/Cv` và `app/Services/Matching` (Phase 04–05).

## Commit: b67b039

### Tiêu đề
feat: Tạo bảng lưu việc, theo dõi công ty và tin nhắn

### Ngày
2026-09-23

### Mục đích
Thay các dữ liệu đang nằm trong `localStorage` và dữ liệu chat giả bằng dữ liệu thật trong database.

### Đã làm
- `saved_jobs`, `company_follows`: bảng trung gian có unique cặp.
- `messages`: gắn với `application_id`. Chỉ khi đã ứng tuyển mới có hội thoại với công ty, nên không ai nhắn rác được.

### Luồng code
Sinh viên ứng tuyển → có `applications` → hai bên nhắn tin qua `messages` của đơn đó.

### File quan trọng
- `database/migrations/2026_09_23_000004_create_saved_jobs_follows_messages_tables.php`

### Kiến thức cần nhớ
- `read_at` null nghĩa là chưa đọc. Đếm tin chưa đọc: `whereNull('read_at')` và người gửi khác mình.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- `down()` xóa `messages` trước vì nó phụ thuộc `applications`.

---

# PHASE 02 — Enum, model, quan hệ, factory, seeder

## Commit: fed8185

### Tiêu đề
feat: Thêm enum vai trò, trạng thái tin và trạng thái đơn ứng tuyển

### Ngày
2026-09-23

### Mục đích
Thay các chuỗi `'student'`, `'open'`, `'interview'` rải rác trong code bằng một chỗ định nghĩa duy nhất, gõ sai là PHP báo lỗi ngay.

### Đã làm
- `Role`: `label()` (tên tiếng Việt), `homePath()` (trang đầu sau đăng nhập).
- `JobStatus`: `open`, `closed`, `hidden`.
- `ApplicationStatus`: 6 trạng thái, `label()`, `step()` (vị trí trên thanh tiến trình), `isFinal()`.

### Luồng code
Database lưu chuỗi → model cast sang enum → code so sánh `$job->status === JobStatus::Open`.

### File quan trọng
- `app/Enums/Role.php`, `app/Enums/JobStatus.php`, `app/Enums/ApplicationStatus.php`

### Kiến thức cần nhớ
- *Backed enum* (`enum X: string`) có `->value` để lưu database và `X::from('...')` để đọc lại.
- `match` bắt buộc liệt kê đủ trường hợp. Thiếu một case là PHP ném `UnhandledMatchError`.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Thêm trạng thái mới: thêm `case`, rồi bổ sung mọi `match` trong enum đó.

## Commit: f81dc6c

### Tiêu đề
feat: Khai báo model và quan hệ cho toàn bộ bảng dữ liệu

### Ngày
2026-09-23

### Mục đích
Để code viết `$student->applications` thay vì tự viết câu SQL nối bảng.

### Đã làm
- Model mới: `Employer`, `Application`, `Cv`, `Message`, `JobRecommendation`.
- `User` → `student()`, `employer()`. `Student` → `skills()` (pivot `source`), `cv()`, `applications()`, `recommendations()`, `savedJobs()`, `followedCompanies()`.
- `JobPost` → `skills()` (pivot `is_required`), `applications()`, scope `open()`, cast `status` sang `JobStatus`.
- `#[Fillable]` liệt kê cột được gán hàng loạt. `Cv` ẩn `path` và `extracted_text` khi chuyển sang JSON.

### Luồng code
`Student::with('skills')->first()` → Eloquent chạy 2 câu SQL (students, rồi student_skill + skills) → gắn kết quả vào `$student->skills`.

### File quan trọng
- `app/Models/*.php`

### Kiến thức cần nhớ
- `belongsTo` đặt ở bảng có khóa ngoại. `hasMany` / `hasOne` đặt ở bảng bị trỏ tới.
- `belongsToMany` dùng cho bảng trung gian. `withPivot` để đọc thêm cột trong bảng đó.
- `Fillable` chống *mass assignment*: `company_id` của tin, `verified` của công ty không nằm trong danh sách, nên người dùng không tự gửi lên để đổi được.
- `#[Scope]` biến `open()` thành `JobPost::open()`.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Cột mới muốn gán bằng `create()`/`update()` phải thêm vào `#[Fillable]`.

## Commit: 0d6053c

### Tiêu đề
feat: Thêm factory cho người dùng, sinh viên, công ty, tin tuyển và đơn

### Ngày
2026-09-23

### Mục đích
Test cần tạo dữ liệu nhanh: `Application::factory()->create()` tự tạo sinh viên, user, tin, công ty đi kèm.

### Đã làm
- `UserFactory` thêm `role` (trước đó thiếu nên factory lỗi vì cột `role` bắt buộc), trạng thái `employer()`, `admin()`, `inactive()`.
- Factory cho `Student`, `Company` (`verified()`), `Employer`, `JobPost` (`closed()`, `hidden()`), `Skill`, `Application`.

### Luồng code
`'student_id' => Student::factory()` → factory tạo `Student` trước, lấy id gắn vào đơn.

### File quan trọng
- `database/factories/*.php`

### Kiến thức cần nhớ
- *State* (`->employer()`) chỉ ghi đè vài cột của `definition()`.
- Factory chạy ở chế độ *unguarded*, nên gán được cả cột ngoài `Fillable`.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Thêm cột bắt buộc (NOT NULL, không default) vào bảng thì phải thêm vào factory.

## Commit: 0caac6b

### Tiêu đề
fix: Khớp số bước tiến trình đơn với giao diện năm bước

### Ngày
2026-09-23

### Mục đích
Trang "Tiến trình" có 5 bước, còn `ApplicationStatus::step()` chỉ trả 1–4.

### Đã làm
- `step()`: Pending 1, Viewed 2, Shortlisted 3, Interview 4, Hired/Rejected 5.

### Luồng code
`step()` → frontend tô các bước `< step` là xong, bước `= step` là hiện tại.

### File quan trọng
- `app/Enums/ApplicationStatus.php`

### Kiến thức cần nhớ
Bug xuất hiện ở: `ApplicationStatus::step()`.
Nguyên nhân: viết enum theo trí nhớ, không đối chiếu `APPLICATIONS` trong `data.js` (5 bước).
Cách sửa: đếm lại bước trên giao diện rồi map lại.
Commit sửa: `0caac6b`.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Timeline trong `public/jobly/js/app.js` (trang tiến trình).

## Commit: d611042

### Tiêu đề
feat: Seed đủ ba vai trò kèm đơn ứng tuyển và tin nhắn mẫu

### Ngày
2026-09-23

### Mục đích
Sau `migrate:fresh --seed` là đăng nhập thử được cả ba vai trò và có dữ liệu để xem.

### Đã làm
- Tài khoản (mật khẩu `password`): `lebao@student.edu.vn`, `minhanh@student.edu.vn`, `quochuy@student.edu.vn`, `admin@jobly.vn`, `hr@<slug>.vn` cho 8 công ty (ví dụ `hr@techwind.vn`).
- Tin 3 và 8 là remote. Kỹ năng cuối của mỗi tin là điểm cộng (`is_required = false`).
- Kỹ năng có `aliases`, ví dụ `JavaScript` → `js`, `es6`.
- 7 đơn, 6 tin nhắn, tin cuối của HR để chưa đọc.
- Mật khẩu băm một lần rồi dùng lại (seed từ 4,7 giây còn khoảng 0,9 giây).

### Luồng code
`DatabaseSeeder::run()` → `JobPlatformSeeder::run()` → công ty → Lê Bảo → tin + kỹ năng → admin → HR → sinh viên khác → đơn + tin nhắn.

### File quan trọng
- `database/seeders/JobPlatformSeeder.php`

### Kiến thức cần nhớ
- Thứ tự seed phải theo khóa ngoại: có công ty rồi mới có tin; có đơn rồi mới có tin nhắn.
- bcrypt chậm có chủ đích để chống dò mật khẩu, nên seeder không băm lại nhiều lần.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Thêm kỹ năng mới: thêm vào `skills` của tin, và vào `ALIASES` nếu muốn nhận diện trong CV.

---

# PHASE 03 — Xác thực và phân quyền

## Commit: 4c041df

### Tiêu đề
refactor: Dùng enum Role cho phân quyền và điều hướng sau đăng nhập

### Ngày
2026-09-23

### Mục đích
Bảng điều hướng theo vai trò (`employer` → `/employer`...) bị viết lặp ở `LoginController` và `bootstrap/app.php`. Gom về `Role::homePath()`.

### Đã làm
- `User` cast `role` sang `Role`, thêm `hasRole(Role ...$roles)`.
- `EnsureRole` đổi chuỗi trong route (`role:student`) sang enum bằng `Role::from`.
- Hành vi giữ nguyên.

### Luồng code
Request → middleware `role:student` → `EnsureRole::handle($request, $next, 'student')` → `Role::from('student')` → `$user->hasRole(...)` → không khớp thì `abort(403)`.

### File quan trọng
- `app/Models/User.php`, `app/Http/Middleware/EnsureRole.php`, `bootstrap/app.php`

### Kiến thức cần nhớ
- `array_map(Role::from(...), $roles)`: cú pháp `Role::from(...)` biến method thành *closure* (first-class callable).
- Refactor là đổi cấu trúc, không đổi hành vi, nên test cũ phải vẫn qua.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Đổi trang đầu của một vai trò: sửa `Role::homePath()`.

## Commit: 574c074

### Tiêu đề
feat: Giới hạn số lần đăng nhập sai và chặn tài khoản bị khóa

### Ngày
2026-09-23

### Mục đích
Chống dò mật khẩu. Tài khoản bị admin khóa không vào được, kể cả khi đang đăng nhập dở.

### Đã làm
- `LoginRequest::authenticate()`: sai 5 lần trong 60 giây theo cặp email + IP thì khóa tạm.
- Tài khoản `is_active = false`: đăng xuất ngay sau `Auth::attempt` và báo lỗi.
- Middleware `EnsureUserIsActive` gắn vào nhóm `web`: đang đăng nhập mà bị khóa thì request kế tiếp bị đăng xuất.

### Luồng code
POST `/login` → `LoginRequest` validate → `authenticate()` → `RateLimiter` → `Auth::attempt` → kiểm tra `is_active` → `session()->regenerate()` → `redirect()->intended(homePath)`.

### File quan trọng
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Middleware/EnsureUserIsActive.php`
- `app/Http/Controllers/Auth/LoginController.php`

### Kiến thức cần nhớ
- *Form Request* gom validate + logic đầu vào, giúp controller gọn.
- `session()->regenerate()` sau đăng nhập chống *session fixation*.
- Thông báo lỗi giống nhau cho "sai email" và "sai mật khẩu", để người ngoài không dò được email nào có tài khoản.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- `MAX_ATTEMPTS` và số giây trong `RateLimiter::hit`.

## Commit: cc138c4

### Tiêu đề
feat: Chuyển thông báo kiểm tra dữ liệu sang tiếng Việt

### Ngày
2026-09-23

### Mục đích
Lỗi form đang hiện "The email field is required".

### Đã làm
- `lang/vi/validation.php` với các rule đang dùng.
- `APP_LOCALE=vi`, `APP_FAKER_LOCALE=vi_VN` trong `.env.example` (máy bạn đã đổi `.env`).

### Luồng code
Validate lỗi → Laravel tìm `lang/{APP_LOCALE}/validation.php` → thay `:attribute`.

### File quan trọng
- `lang/vi/validation.php`, `.env.example`

### Kiến thức cần nhớ
- `:Attribute` (viết hoa) in hoa chữ đầu, `:attribute` giữ nguyên.
- Tên trường tiếng Việt đặt trong `attributes()` của Form Request hoặc mảng `attributes` của file lang.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Rule chưa có câu tiếng Việt sẽ hiện key thô như `validation.xxx`: thêm vào file lang.

## Commit: 481e01e

### Tiêu đề
feat: Cho nhà tuyển dụng đăng ký kèm tạo công ty

### Ngày
2026-09-23

### Mục đích
Trước đây chỉ sinh viên đăng ký được. Nhà tuyển dụng cần tài khoản gắn với một công ty.

### Đã làm
- `RegistrationService`: `registerStudent`, `registerEmployer` (tạo `users` + `companies` + `employers` trong một transaction, slug không trùng, màu logo cố định theo tên).
- `RegisterRequest` dùng chung. Route employer thêm trường công ty. Mật khẩu ≥ 8 ký tự, có chữ và số.
- Route `/register/employer`. Hai route POST có `throttle:10,1`.
- Tách `auth/layout.blade.php`. Form có tab Sinh viên / Nhà tuyển dụng. Trang login có link sang đăng ký.

### Luồng code
POST `/register/employer` → `RegisterRequest` (`isEmployer()` theo tên route) → `RegisterController::store` → `RegistrationService::registerEmployer` → `Auth::login` → `/employer`.

### File quan trọng
- `app/Services/Auth/RegistrationService.php`
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Controllers/Auth/RegisterController.php`
- `resources/views/auth/*.blade.php`

### Kiến thức cần nhớ
- Service chứa nghiệp vụ, controller chỉ nhận request và trả response.
- `role` không lấy từ form, nên gửi thêm `role=admin` cũng vô ích (đã có test chứng minh).
- Công ty mới có `verified = false`, admin xác thực sau.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Thêm trường công ty: `RegisterRequest::rules()` → `RegistrationService::registerEmployer` → form.

## Commit: 86a0832

### Tiêu đề
feat: Thêm policy kiểm soát quyền với tin tuyển, đơn và CV

### Ngày
2026-09-23

### Mục đích
Middleware `role` chỉ biết "bạn là ai". Policy trả lời "bạn có được đụng vào bản ghi này không": HR công ty A không sửa được tin của công ty B.

### Đã làm
- `JobPostPolicy`: `view` (tin đóng/ẩn chỉ chủ và admin), `create`, `update` (tin bị admin ẩn thì không tự mở lại), `delete`, `moderate`.
- `ApplicationPolicy`: `view`, `updateStatus` (không đổi đơn đã có kết quả), `message`, `withdraw`.
- `CvPolicy::download`: HR chỉ tải CV của sinh viên đã nộp vào công ty mình.
- `User::companyId()`.

### Luồng code
Controller gọi `$this->authorize('update', $job)` hoặc `Gate::authorize(...)` → Laravel tìm `JobPostPolicy::update` theo tên model → `false` thì 403.

### File quan trọng
- `app/Policies/*.php`

### Kiến thức cần nhớ
- Laravel tự tìm policy theo quy ước tên `App\Models\X` → `App\Policies\XPolicy`.
- Tham số `?User $user` cho phép khách (chưa đăng nhập) đi vào policy.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- `tests/Feature/Auth/AuthorizationTest.php`.

## Commit: 8fa691a

### Tiêu đề
test: Kiểm thử đăng nhập, đăng ký và phân quyền

### Ngày
2026-09-23

### Mục đích
Chứng minh các luật ở trên đúng, và báo ngay nếu sau này ai sửa làm hỏng.

### Đã làm
- `LoginTest` (7 test): đúng/sai mật khẩu, tài khoản khóa, khóa giữa phiên, giới hạn số lần, đăng xuất.
- `RegisterTest` (6 test): sinh viên, nhà tuyển dụng, slug trùng, thiếu tên công ty, chèn `role`, email trùng + mật khẩu yếu.
- `AuthorizationTest` (7 test): trang công khai, chuyển về login, 403, policy tin/đơn/CV.

### Luồng code
`php artisan test` → `RefreshDatabase` chạy migrate trên SQLite `:memory:` → mỗi test chạy trong transaction rồi rollback.

### File quan trọng
- `tests/Feature/Auth/*.php`, `phpunit.xml`

### Kiến thức cần nhớ
- Test đặt tên theo hành vi (`test_locked_account_cannot_log_in`), đọc tên là biết luật.
- `assertSessionHasErrors`, `assertAuthenticatedAs`, `assertForbidden` là các assert hay dùng nhất.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- `php artisan test --filter=LoginTest`.

---

# PHASE 04 — Xử lý CV

Pipeline của phase này: **INPUT** (file) → **DATA EXTRACTION** (chữ thô) → **NORMALIZATION** (chữ chuẩn hóa) → **FEATURE EXTRACTION** (kỹ năng, liên hệ, học vấn).

## Commit: b30deb4

### Tiêu đề
feat: Trích xuất chữ từ CV dạng PDF và DOCX

### Ngày
2026-09-23

### Mục đích
Máy chưa "đọc" được CV nếu chưa lấy được chữ ra khỏi file.

### Đã làm
- `CvTextExtractor::extract($path, $mime)`: PDF qua `smalot/pdfparser`, DOCX qua `ZipArchive` đọc `word/document.xml`.
- File hỏng hoặc sai định dạng thì ném `RuntimeException` với câu tiếng Việt.
- `tests/Support/CvFiles.php` tự tạo file DOCX/PDF thật khi test, repo không cần chứa file nhị phân.

### Luồng code
`extract()` → `match ($mime)` → `fromPdf()` hoặc `fromDocx()` → `trim()`.

### File quan trọng
- `app/Services/Cv/CvTextExtractor.php`
- `tests/Unit/CvTextExtractorTest.php`

### Kiến thức cần nhớ
- DOCX thực chất là file zip chứa XML. `</w:p>` là hết một đoạn.
- PDF dạng ảnh scan không có chữ. Bước này trả về chuỗi rỗng, bước sau xử lý.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Hỗ trợ thêm định dạng: thêm hằng MIME + nhánh `match` + test.

## Commit: 03ac688

### Tiêu đề
feat: Chuẩn hóa văn bản và nhận diện kỹ năng, liên hệ, học vấn trong CV

### Ngày
2026-09-23

### Mục đích
"ReactJS", "react.js", "React" phải được hiểu là cùng một kỹ năng. "Java" không được khớp nhầm vào "JavaScript".

### Đã làm
- `TextNormalizer`: `normalize()` (thường, bỏ dấu, giữ `+ # . /`), `containsTerm()` (khớp đúng ranh giới từ), `keywords()` (bỏ stopword).
- `SkillExtractor`: dò tên + `aliases` của từng kỹ năng trong danh mục. Viết tắt ≤ 2 ký tự (`AI`, `UI`) chỉ tính khi viết HOA, để chữ "ai" trong câu tiếng Việt không bị nhận nhầm.
- `CvParser::parse()` trả về `skills`, `email`, `phone`, `education`, `experience_mentions`, `keywords`, `word_count`. Không tìm thấy thì để `null`, không đoán.

### Luồng code
Chữ thô → `TextNormalizer::normalize` → `SkillExtractor::extract` (so với danh mục) + regex email/sđt/trường → mảng `parsed`.

### File quan trọng
- `app/Support/TextNormalizer.php`
- `app/Services/Cv/SkillExtractor.php`, `app/Services/Cv/CvParser.php`
- `tests/Unit/CvParsingTest.php`

### Kiến thức cần nhớ
- *Lookbehind/lookahead* `(?<![a-z0-9])` … `(?![a-z0-9])` là cách viết "ranh giới từ" khi tên có ký tự đặc biệt như `c++`, vì `\b` không xử lý được.
- Đây là nhận diện bằng luật (rule-based), giải thích được vì sao ra kết quả. Không phải mô hình học máy.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Kỹ năng không được nhận ra: thêm cách viết vào `skills.aliases`.

## Commit: c74d1fe

### Tiêu đề
feat: Lưu CV riêng tư, cập nhật kỹ năng từ CV và chấm độ đầy đủ hồ sơ

### Ngày
2026-09-23

### Mục đích
Nối các bước trên thành một luồng hoàn chỉnh khi sinh viên tải CV.

### Đã làm
- `CvService::upload()`: xác định loại file → lưu vào `storage/app/private/cvs/{student_id}/<ngẫu-nhiên>.pdf|docx` → phân tích → lưu `cvs` (`parse_status`: `parsed`, `empty`, `failed`) → đồng bộ kỹ năng `source = cv` → xóa file cũ → chấm lại `profile_score`.
- `CvService::delete()`: xóa file, bản ghi và kỹ năng lấy từ CV.
- `ProfileScoreCalculator`: 8 tiêu chí, tổng 100, trả kèm danh sách còn thiếu để gợi ý.
- Hai lỗi phát hiện khi viết test, sửa trước khi commit:
  - Bug xuất hiện ở: tải CV lần hai không xóa file cũ. Nguyên nhân: `$student->cv` trả quan hệ đã nạp lúc trước (null). Cách sửa: truy vấn lại `$student->cv()->value('path')`.
  - Bug xuất hiện ở: file DOCX lưu với đuôi `.zip` và bị báo "không hỗ trợ". Nguyên nhân: libmagic nhận một số DOCX là `application/zip`. Cách sửa: `detectType()` kiểm tra đuôi `.docx` + có `word/document.xml`; tự đặt đuôi file khi lưu.

### Luồng code
Controller (Phase 06) → `CvService::upload($student, $file)` → `detectType` → `storeAs` → `analyze` (`CvTextExtractor` → `CvParser`) → transaction (`Cv::updateOrCreate` + `syncCvSkills`) → `ProfileScoreCalculator::refresh`.

### File quan trọng
- `app/Services/Cv/CvService.php`
- `app/Services/Profile/ProfileScoreCalculator.php`
- `tests/Feature/CvServiceTest.php`

### Kiến thức cần nhớ
- Disk `local` nằm ở `storage/app/private`, không có URL công khai. Muốn tải file phải qua controller có kiểm tra `CvPolicy`.
- Không dùng tên file người dùng gửi để lưu, tránh `../../` và ghi đè.
- Kỹ năng `manual` là lựa chọn của sinh viên, CV mới không được xóa chúng.
- Quan hệ Eloquent đã nạp sẽ được cache trên object. Cần số liệu mới thì gọi `->cv()` (query) hoặc `->fresh()`.

### Nếu muốn sửa chức năng này
Cần kiểm tra:
- Đổi trọng số độ đầy đủ: `ProfileScoreCalculator::CRITERIA` (tổng phải bằng 100).

<!-- mục-tiếp-theo -->
