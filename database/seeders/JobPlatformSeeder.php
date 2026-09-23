<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Copy từ public/jobly/js/data.js — cùng nội dung, khác cú pháp PHP + tách bảng.
 * Tài khoản mẫu (mật khẩu đều là "password"): lebao@student.edu.vn, admin@jobly.vn, hr@<slug-công-ty>.vn.
 */
class JobPlatformSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Cách viết khác của kỹ năng — dùng để nhận diện kỹ năng trong CV. */
    private const ALIASES = [
        'JavaScript' => ['js', 'javascript', 'es6'],
        'HTML/CSS' => ['html', 'css', 'html5', 'css3'],
        'React' => ['reactjs', 'react.js'],
        'PHP' => ['php'],
        'Laravel' => ['laravel'],
        'SQL' => ['sql', 'mysql', 'postgresql', 'sqlite'],
        'API' => ['rest api', 'restful', 'api'],
        'Git' => ['git', 'github', 'gitlab'],
        'Python' => ['python', 'pandas'],
        'Excel' => ['excel', 'google sheets'],
        'Dashboard' => ['dashboard', 'power bi', 'looker studio', 'tableau'],
        'Figma' => ['figma'],
        'Photoshop' => ['photoshop', 'ps'],
        'Illustrator' => ['illustrator', 'ai'],
        'UI/UX' => ['ui/ux', 'ui', 'ux', 'wireframe', 'prototype'],
        'Canva' => ['canva'],
        'TikTok' => ['tiktok'],
        'Instagram' => ['instagram', 'ig'],
        'Copywriting' => ['copywriting', 'viết content', 'content writing'],
        'Marketing' => ['marketing', 'digital marketing'],
    ];

    private ?string $hashedPassword = null;

    /** Băm "password" một lần rồi dùng lại — bcrypt chậm có chủ đích. */
    private function matKhau(): string
    {
        return $this->hashedPassword ??= Hash::make('password');
    }

    /**
     * Laravel bắt buộc tên run() — không đổi.
     * Ba hàm tiếng Việt bên dưới là mình viết.
     */
    public function run(): void
    {
        $now = now();
        $companies = $this->nhetCongTy($now);
        $studentId = $this->nhetSinhVien($now);
        $this->nhetViecLam($companies, $studentId, $now);
        $this->nhetQuanTri($now);
        $this->nhetNhaTuyenDung($companies, $now);
        $this->nhetSinhVienKhac($now);
        $this->nhetDonVaTinNhan($studentId, $now);
    }

    /** Nhét các công ty từ COMPANIES (data.js) vào bảng companies. */
    private function nhetCongTy(mixed $now): array
    {
        $rows = [
            [
                'slug' => 'may-creative',
                'name' => 'Mây Creative',
                'verified' => true,
                'tagline' => 'Công ty sáng tạo nội dung & thiết kế',
                'size' => '1.000–5.000 nhân sự',
                'location' => 'Quận 1, TP.HCM',
                'color' => '#7C5CFF',
                'initial' => 'M',
                'about' => 'Mây Creative thiết kế trải nghiệm số cho thương hiệu trẻ. Team nhỏ, nhịp làm việc linh hoạt — phù hợp sinh viên muốn học UI/UX thật.',
                'rating' => 4.8,
                'reviews_count' => 128,
            ],
            [
                'slug' => 'techwind',
                'name' => 'TechWind',
                'verified' => true,
                'tagline' => 'Product studio',
                'size' => '50–200 nhân sự',
                'location' => 'Quận 7, TP.HCM',
                'color' => '#2563EB',
                'initial' => 'T',
                'about' => 'TechWind xây sản phẩm web cho startup. Frontend team dùng React, thích người chịu học và code sạch.',
                'rating' => 4.6,
                'reviews_count' => 86,
            ],
            [
                'slug' => 'storylab',
                'name' => 'StoryLab',
                'verified' => true,
                'tagline' => 'Content house',
                'size' => '20–50 nhân sự',
                'location' => 'Bình Thạnh, TP.HCM',
                'color' => '#EC4899',
                'initial' => 'S',
                'about' => 'StoryLab làm nội dung cho brand Gen Z. Môi trường trẻ, deadline rõ, mentor kèm từng brief.',
                'rating' => 4.7,
                'reviews_count' => 54,
            ],
            [
                'slug' => 'novastack',
                'name' => 'NovaStack',
                'verified' => true,
                'tagline' => 'Backend & cloud',
                'size' => '200–500 nhân sự',
                'location' => 'Thủ Đức, TP.HCM',
                'color' => '#0EA5E9',
                'initial' => 'N',
                'about' => 'NovaStack chuyên API và hạ tầng. Intern/part-time được pair với senior, code review hàng tuần.',
                'rating' => 4.5,
                'reviews_count' => 91,
            ],
            [
                'slug' => 'bloom-agency',
                'name' => 'Bloom Agency',
                'verified' => false,
                'tagline' => 'Performance marketing',
                'size' => '20–50 nhân sự',
                'location' => 'Quận 3, TP.HCM',
                'color' => '#F59E0B',
                'initial' => 'B',
                'about' => 'Bloom chạy campaign cho F&B và edtech. Intern được cầm việc thật, không chỉ đi meeting.',
                'rating' => 4.4,
                'reviews_count' => 37,
            ],
            [
                'slug' => 'datanest',
                'name' => 'DataNest',
                'verified' => true,
                'tagline' => 'Data for decisions',
                'size' => '50–200 nhân sự',
                'location' => 'Quận 1, TP.HCM',
                'color' => '#10B981',
                'initial' => 'D',
                'about' => 'DataNest giúp SME đọc hiểu dữ liệu. Intern học SQL, dashboard và cách kể chuyện bằng số.',
                'rating' => 4.9,
                'reviews_count' => 62,
            ],
            [
                'slug' => 'pixel-co',
                'name' => 'Pixel & Co',
                'verified' => true,
                'tagline' => 'Visual studio',
                'size' => '10–50 nhân sự',
                'location' => 'Phú Nhuận, TP.HCM',
                'color' => '#F43F5E',
                'initial' => 'P',
                'about' => 'Studio thiết kế nhận diện và social visual. Làm việc theo sprint ngắn, feedback nhanh.',
                'rating' => 4.6,
                'reviews_count' => 44,
            ],
            [
                'slug' => 'wave-social',
                'name' => 'Wave Social',
                'verified' => true,
                'tagline' => 'Social-first agency',
                'size' => '20–50 nhân sự',
                'location' => 'Quận 2, TP.HCM',
                'color' => '#8B5CF6',
                'initial' => 'W',
                'about' => 'Wave Social vận hành kênh TikTok/IG cho brand. Ca làm việc linh hoạt, phù hợp lịch học.',
                'rating' => 4.5,
                'reviews_count' => 71,
            ],
        ];

        $ids = [];
        foreach ($rows as $row) {
            $slug = $row['slug'];
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            $ids[$slug] = DB::table('companies')->insertGetId($row);
        }

        return $ids;
    }

    /** USER 1 object JS → users (đăng nhập) + students (hồ sơ). */
    private function nhetSinhVien(mixed $now): int
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Lê Bảo',
            'email' => 'lebao@student.edu.vn',
            'role' => 'student',
            'password' => $this->matKhau(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('students')->insertGetId([
            'user_id' => $userId,
            'school' => 'Đại học Công Nghệ',
            'major' => 'Công nghệ thông tin',
            'year' => 'Sinh viên năm 2',
            'location' => 'TP. Hồ Chí Minh',
            'phone' => '0901 234 567',
            'bio' => 'Sinh viên năm 2, thích UI/UX và frontend. Muốn tìm việc part-time để học hỏi và tích lũy kinh nghiệm thực tế.',
            'avatar' => 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?auto=format&fit=crop&w=400&q=80',
            'profile_score' => 82,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** JOBS[] → job_posts; skills[] → skills + job_skill / student_skill. */
    private function nhetViecLam(array $companies, int $studentId, mixed $now): void
    {
        $jobs = [
            [
                'id' => 1,
                'company_slug' => 'may-creative',
                'title' => 'UI/UX Designer',
                'salary' => '10–13 triệu/tháng',
                'location' => 'Quận 1, TP.HCM',
                'type' => 'Part-time',
                'hours' => '3–5 giờ/ngày',
                'image' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?auto=format&fit=crop&w=1200&q=80',
                'description' => 'Thiết kế giao diện cho app và landing page của khách hàng trẻ. Bạn sẽ làm wireframe, UI kit và prototype trên Figma, làm việc trực tiếp với PM và developer.',
                'requirements' => [
                    'Biết Figma (wireframe, auto-layout là lợi thế)',
                    'Có cảm quan thị giác, portfolio dù nhỏ cũng được',
                    'Sinh viên năm 2 trở lên, làm được 3–5 giờ/ngày',
                    'Giao tiếp tiếng Việt rõ ràng, nhận feedback tốt',
                ],
                'benefits' => [
                    'Mentor 1:1 với lead designer',
                    'Lịch ca linh hoạt theo tuần học',
                    'Được đứng tên trên case study (nếu release)',
                    'Hỗ trợ ăn trưa những ngày onsite',
                ],
                'skills' => ['Figma', 'Photoshop', 'UI/UX', 'Creative'],
            ],
            [
                'id' => 2,
                'company_slug' => 'techwind',
                'title' => 'Frontend Developer',
                'salary' => '18–22 triệu/tháng',
                'location' => 'Quận 7, TP.HCM',
                'type' => 'Part-time',
                'hours' => '3–5 giờ/ngày',
                'image' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1200&q=80',
                'description' => 'Làm UI các module nhỏ trên dashboard sản phẩm. Code HTML/CSS/JS, dần làm quen React dưới sự hướng dẫn của senior.',
                'requirements' => [
                    'Nắm JavaScript ES6 và HTML/CSS',
                    'Biết Git cơ bản',
                    'Ham học React (chưa biết vẫn nhận)',
                    'Có thể onsite 2 buổi/tuần tại Quận 7',
                ],
                'benefits' => [
                    'Pair programming với senior',
                    'Review code mỗi tuần',
                    'Có thể chuyển full-time sau 6 tháng',
                    'Máy làm việc nếu onsite',
                ],
                'skills' => ['JavaScript', 'HTML/CSS', 'React', 'Git'],
            ],
            [
                'id' => 3,
                'is_remote' => true,
                'company_slug' => 'storylab',
                'title' => 'Content Creator',
                'salary' => '8–12 triệu/tháng',
                'location' => 'Bình Thạnh, TP.HCM',
                'type' => 'Part-time',
                'hours' => '4 giờ/ngày',
                'image' => 'https://images.unsplash.com/photo-1559136555-9303baea8ebd?auto=format&fit=crop&w=1200&q=80',
                'description' => 'Viết script ngắn, caption và ý tưởng video cho brand Gen Z. Làm việc theo brief tuần, không cần xuất hiện trước camera.',
                'requirements' => [
                    'Viết tiếng Việt tự nhiên, đúng chính tả',
                    'Am hiểu TikTok/Reels',
                    'Biết Canva là lợi thế',
                    'Deadline rõ, chủ động hỏi khi kẹt brief',
                ],
                'benefits' => [
                    'Được credit trên video nếu script được dùng',
                    'Workshop kể chuyện mỗi tháng',
                    'Hybrid: 1 ngày onsite / còn lại remote',
                    'Thưởng theo video viral',
                ],
                'skills' => ['Copywriting', 'TikTok', 'Canva', 'Storytelling'],
            ],
            [
                'id' => 4,
                'company_slug' => 'novastack',
                'title' => 'Backend Developer',
                'salary' => '16–20 triệu/tháng',
                'location' => 'Thủ Đức, TP.HCM',
                'type' => 'Part-time',
                'hours' => '4–6 giờ/ngày',
                'image' => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=1200&q=80',
                'description' => 'Tham gia viết API nhỏ, tối ưu query và sửa bug trên hệ thống Laravel. Được gán ticket vừa sức, có senior review.',
                'requirements' => [
                    'Biết PHP và SQL',
                    'Đã từng làm Laravel hoặc MVC tương tự',
                    'Hiểu REST API cơ bản',
                    'Sinh viên năm 2+, cam kết ít nhất 4 giờ/ngày',
                ],
                'benefits' => [
                    'Mentor backend có kinh nghiệm product',
                    'Được đụng code production (sau onboarding)',
                    'Hỗ trợ gửi xe + trà/cà phê',
                    'Lộ trình intern → junior rõ',
                ],
                'skills' => ['PHP', 'SQL', 'Laravel', 'API'],
            ],
            [
                'id' => 5,
                'company_slug' => 'bloom-agency',
                'title' => 'Marketing Intern',
                'salary' => '5–8 triệu/tháng',
                'location' => 'Quận 3, TP.HCM',
                'type' => 'Thực tập',
                'hours' => '4 giờ/ngày',
                'image' => 'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=80',
                'description' => 'Hỗ trợ research thị trường, lên lịch bài post và theo dõi báo cáo campaign. Phù hợp bạn muốn hiểu marketing từ gốc.',
                'requirements' => [
                    'Chăm chỉ, chủ động ghi chép',
                    'Excel/Google Sheet cơ bản',
                    'Ham học quảng cáo số',
                    'Onsite 4 buổi/tuần',
                ],
                'benefits' => [
                    'Được cầm 1 mini-campaign sau tháng đầu',
                    'Mentor performance',
                    'Certificate khi hoàn thành 3 tháng',
                    'Xét lương khi lên part-time',
                ],
                'skills' => ['Marketing', 'Canva', 'Excel', 'Research'],
            ],
            [
                'id' => 6,
                'company_slug' => 'datanest',
                'title' => 'Data Analyst Intern',
                'salary' => '7–10 triệu/tháng',
                'location' => 'Quận 1, TP.HCM',
                'type' => 'Thực tập',
                'hours' => '3–5 giờ/ngày',
                'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=1200&q=80',
                'description' => 'Làm sạch data, viết query SQL và vẽ dashboard đơn giản cho khách SME. Học cách đặt câu hỏi từ số liệu.',
                'requirements' => [
                    'SQL cơ bản (SELECT, JOIN)',
                    'Excel thành thạo',
                    'Python là lợi thế',
                    'Cẩn thận, thích soi số',
                ],
                'benefits' => [
                    'Dataset thật, không phải bài tập',
                    'Kèm 1 analyst trong 4 tuần đầu',
                    'Hybrid 2 ngày/tuần',
                    'Cơ hội stay sau kỳ thực tập',
                ],
                'skills' => ['SQL', 'Excel', 'Python', 'Dashboard'],
            ],
            [
                'id' => 7,
                'company_slug' => 'pixel-co',
                'title' => 'Graphic Designer',
                'salary' => '9–12 triệu/tháng',
                'location' => 'Phú Nhuận, TP.HCM',
                'type' => 'Part-time',
                'hours' => '3–4 giờ/ngày',
                'image' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?auto=format&fit=crop&w=1200&q=80',
                'description' => 'Thiết kế key visual, banner social và asset campaign. Làm việc theo moodboard, deadline từng wave.',
                'requirements' => [
                    'Photoshop hoặc Illustrator',
                    'Có 3–5 sản phẩm để xem (béan cũng được)',
                    'Hiểu hierarchy và màu',
                    'Nhận feedback không tự ái',
                ],
                'benefits' => [
                    'Portfolio review mỗi tháng',
                    'Được in ấn một số ấn phẩm thật',
                    'Ca ngắn, dễ xếp lịch học',
                    'Môi trường studio yên, ít họp',
                ],
                'skills' => ['Photoshop', 'Illustrator', 'Branding', 'Figma'],
            ],
            [
                'id' => 8,
                'is_remote' => true,
                'company_slug' => 'wave-social',
                'title' => 'Social Media Intern',
                'salary' => '6–9 triệu/tháng',
                'location' => 'Quận 2, TP.HCM',
                'type' => 'Thực tập',
                'hours' => '3–5 giờ/ngày',
                'image' => 'https://images.unsplash.com/photo-1611162617474-5b21e879e113?auto=format&fit=crop&w=1200&q=80',
                'description' => 'Lên lịch đăng, soạn caption và theo dõi insight kênh. Thỉnh thoảng hỗ trợ quay clip ngắn tại văn phòng.',
                'requirements' => [
                    'Dùng TikTok/IG thành thạo',
                    'Viết caption vui, không sến',
                    'Canva cơ bản',
                    'Có mặt 3 buổi/tuần',
                ],
                'benefits' => [
                    'Được thử format content mới',
                    'Lịch ca theo tuần',
                    'Team Gen Z, ít formal',
                    'Thưởng KPI theo tăng follow',
                ],
                'skills' => ['TikTok', 'Instagram', 'Caption', 'Canva'],
            ],
        ];

        $skillIds = [];
        // Tạo skill nếu chưa có, trả về id — tránh insert trùng tên.
        $damBaoKyNang = function (string $name) use (&$skillIds, $now): int {
            if (! isset($skillIds[$name])) {
                $skillIds[$name] = DB::table('skills')->insertGetId([
                    'name' => $name,
                    'aliases' => json_encode(self::ALIASES[$name] ?? [], JSON_UNESCAPED_UNICODE),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $skillIds[$name];
        };

        foreach (['PHP', 'JavaScript', 'SQL', 'HTML/CSS', 'Figma'] as $name) {
            $damBaoKyNang($name);
        }

        foreach ($jobs as $job) {
            $skillNames = $job['skills'];
            unset($job['skills']);
            $slug = $job['company_slug'];
            unset($job['company_slug']);

            $job['company_id'] = $companies[$slug];
            $job['requirements'] = json_encode($job['requirements'], JSON_UNESCAPED_UNICODE);
            $job['benefits'] = json_encode($job['benefits'], JSON_UNESCAPED_UNICODE);
            $job['created_at'] = $now;
            $job['updated_at'] = $now;

            DB::table('job_posts')->insert($job);

            // Kỹ năng cuối trong danh sách là "điểm cộng", các kỹ năng trước là bắt buộc.
            $last = array_key_last($skillNames);
            foreach ($skillNames as $i => $name) {
                DB::table('job_skill')->insert([
                    'job_post_id' => $job['id'],
                    'skill_id' => $damBaoKyNang($name),
                    'is_required' => $i !== $last,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        foreach (['PHP', 'JavaScript', 'SQL', 'HTML/CSS', 'Figma'] as $name) {
            DB::table('student_skill')->insert([
                'student_id' => $studentId,
                'skill_id' => $skillIds[$name],
                'source' => 'manual',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** Tài khoản quản trị: admin@jobly.vn / password. */
    private function nhetQuanTri(mixed $now): void
    {
        DB::table('users')->insert([
            'name' => 'Quản trị Jobly',
            'email' => 'admin@jobly.vn',
            'role' => 'admin',
            'password' => $this->matKhau(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** Mỗi công ty một tài khoản HR: hr@<slug>.vn / password. */
    private function nhetNhaTuyenDung(array $companies, mixed $now): void
    {
        foreach ($companies as $slug => $companyId) {
            $name = DB::table('companies')->where('id', $companyId)->value('name');

            $userId = DB::table('users')->insertGetId([
                'name' => 'HR '.$name,
                'email' => "hr@{$slug}.vn",
                'role' => 'employer',
                'password' => $this->matKhau(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('employers')->insert([
                'user_id' => $userId,
                'company_id' => $companyId,
                'position' => 'Chuyên viên tuyển dụng',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** Thêm hai sinh viên để nhà tuyển dụng có nhiều ứng viên khác nhau. */
    private function nhetSinhVienKhac(mixed $now): void
    {
        $people = [
            [
                'name' => 'Trần Minh Anh',
                'email' => 'minhanh@student.edu.vn',
                'student' => ['school' => 'Đại học Kinh tế', 'major' => 'Hệ thống thông tin', 'year' => 'Sinh viên năm 3', 'bio' => 'Thích phân tích dữ liệu, dùng Excel và SQL hằng ngày.'],
                'skills' => ['SQL', 'Excel', 'Python'],
                'apply' => [6 => 'pending', 5 => 'pending'],
            ],
            [
                'name' => 'Phạm Quốc Huy',
                'email' => 'quochuy@student.edu.vn',
                'student' => ['school' => 'Đại học Bách Khoa', 'major' => 'Khoa học máy tính', 'year' => 'Sinh viên năm 4', 'bio' => 'Làm backend Laravel hơn một năm qua các dự án môn học.'],
                'skills' => ['PHP', 'Laravel', 'SQL', 'Git', 'API'],
                'apply' => [4 => 'shortlisted', 2 => 'rejected'],
            ],
        ];

        foreach ($people as $person) {
            $userId = DB::table('users')->insertGetId([
                'name' => $person['name'],
                'email' => $person['email'],
                'role' => 'student',
                'password' => $this->matKhau(),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $studentId = DB::table('students')->insertGetId($person['student'] + [
                'user_id' => $userId,
                'location' => 'TP. Hồ Chí Minh',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($person['skills'] as $name) {
                DB::table('student_skill')->insert([
                    'student_id' => $studentId,
                    'skill_id' => DB::table('skills')->where('name', $name)->value('id'),
                    'source' => 'manual',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($person['apply'] as $jobId => $status) {
                DB::table('applications')->insert([
                    'student_id' => $studentId,
                    'job_post_id' => $jobId,
                    'status' => $status,
                    'created_at' => $now->copy()->subDays(3),
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /** Đơn và hội thoại của Lê Bảo — giống APPLICATIONS và CONVERSATIONS trong data.js cũ. */
    private function nhetDonVaTinNhan(int $studentId, mixed $now): void
    {
        $studentUserId = DB::table('students')->where('id', $studentId)->value('user_id');

        $applications = [
            1 => ['status' => 'interview', 'days' => 19, 'messages' => [
                ['hr', 'Chào Bảo! Cảm ơn bạn đã quan tâm đến vị trí UI/UX Designer.'],
                ['student', 'Dạ vâng, em đã tìm hiểu thêm về Mây Creative và rất thích cách team làm sản phẩm.'],
                ['hr', 'Chúng mình muốn mời bạn tham gia một buổi phỏng vấn online. Bạn chọn giúp một khung giờ nhé.'],
            ]],
            2 => ['status' => 'shortlisted', 'days' => 16, 'messages' => [
                ['student', 'Em đã gửi bài test qua email ạ.'],
                ['hr', 'Cảm ơn bạn đã gửi bài test. Team sẽ phản hồi trong 2 ngày.'],
            ]],
            6 => ['status' => 'viewed', 'days' => 15, 'messages' => [
                ['hr', 'Bạn có thể gửi thêm portfolio dashboard không?'],
            ]],
        ];

        foreach ($applications as $jobId => $app) {
            $created = $now->copy()->subDays($app['days']);

            $applicationId = DB::table('applications')->insertGetId([
                'student_id' => $studentId,
                'job_post_id' => $jobId,
                'status' => $app['status'],
                'created_at' => $created,
                'updated_at' => $now,
            ]);

            $companyId = DB::table('job_posts')->where('id', $jobId)->value('company_id');
            $hrUserId = DB::table('employers')->where('company_id', $companyId)->value('user_id');

            foreach ($app['messages'] as $i => [$from, $body]) {
                $sentAt = $created->copy()->addDays(2)->addMinutes($i * 5);
                $isLast = $i === array_key_last($app['messages']);

                DB::table('messages')->insert([
                    'application_id' => $applicationId,
                    'user_id' => $from === 'hr' ? $hrUserId : $studentUserId,
                    'body' => $body,
                    // Tin cuối của HR để chưa đọc, sinh viên sẽ thấy badge.
                    'read_at' => ($from === 'hr' && $isLast) ? null : $sentAt,
                    'created_at' => $sentAt,
                    'updated_at' => $sentAt,
                ]);
            }
        }
    }
}
