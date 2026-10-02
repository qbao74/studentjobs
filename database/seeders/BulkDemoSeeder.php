<?php

namespace Database\Seeders;

use App\Models\Skill;
use App\Services\Cv\CvParser;
use App\Services\Matching\RecommendationService;
use App\Services\Profile\ProfileScoreCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Thêm 40 công ty (mỗi công ty một HR và 5 tin), 40 sinh viên có CV và đơn ứng tuyển.
 * Tài khoản mới dùng mật khẩu "password". Chạy lại sẽ bỏ qua nếu slug demo- đã có.
 */
class BulkDemoSeeder extends Seeder
{
    /** @var list<array{slug: string, name: string, tagline: string, size: string, location: string, color: string}> */
    private const COMPANIES = [
        ['slug' => 'lan-anh-studio', 'name' => 'Lan Anh Studio', 'tagline' => 'Thiết kế sản phẩm số', 'size' => '10–50 nhân sự', 'location' => 'Quận 1, TP.HCM', 'color' => '#7C5CFF'],
        ['slug' => 'sao-bac-digital', 'name' => 'Sao Bắc Digital', 'tagline' => 'Gia công phần mềm', 'size' => '50–200 nhân sự', 'location' => 'Quận 3, TP.HCM', 'color' => '#2563EB'],
        ['slug' => 'hong-ha-tech', 'name' => 'Hồng Hà Tech', 'tagline' => 'Web cho SME', 'size' => '20–50 nhân sự', 'location' => 'Quận 7, TP.HCM', 'color' => '#0EA5E9'],
        ['slug' => 'viet-tin-software', 'name' => 'Việt Tín Software', 'tagline' => 'Phần mềm nội bộ', 'size' => '200–500 nhân sự', 'location' => 'Thủ Đức, TP.HCM', 'color' => '#10B981'],
        ['slug' => 'an-khang-media', 'name' => 'An Khang Media', 'tagline' => 'Nội dung thương hiệu', 'size' => '20–50 nhân sự', 'location' => 'Bình Thạnh, TP.HCM', 'color' => '#EC4899'],
        ['slug' => 'binh-minh-labs', 'name' => 'Bình Minh Labs', 'tagline' => 'Product lab', 'size' => '50–200 nhân sự', 'location' => 'Quận 1, TP.HCM', 'color' => '#F59E0B'],
        ['slug' => 'cat-tuong-design', 'name' => 'Cát Tường Design', 'tagline' => 'Nhận diện thương hiệu', 'size' => '10–50 nhân sự', 'location' => 'Phú Nhuận, TP.HCM', 'color' => '#F43F5E'],
        ['slug' => 'dong-a-analytics', 'name' => 'Đông Á Analytics', 'tagline' => 'Phân tích dữ liệu', 'size' => '50–200 nhân sự', 'location' => 'Quận 1, TP.HCM', 'color' => '#14B8A6'],
        ['slug' => 'gia-hung-code', 'name' => 'Gia Hưng Code', 'tagline' => 'Outsource web', 'size' => '20–50 nhân sự', 'location' => 'Quận 10, TP.HCM', 'color' => '#6366F1'],
        ['slug' => 'hai-yen-content', 'name' => 'Hải Yến Content', 'tagline' => 'Content house', 'size' => '10–50 nhân sự', 'location' => 'Quận 2, TP.HCM', 'color' => '#A855F7'],
        ['slug' => 'kim-ngan-it', 'name' => 'Kim Ngân IT', 'tagline' => 'Hệ thống doanh nghiệp', 'size' => '200–500 nhân sự', 'location' => 'Cầu Giấy, Hà Nội', 'color' => '#2563EB'],
        ['slug' => 'long-van-agency', 'name' => 'Long Vân Agency', 'tagline' => 'Performance marketing', 'size' => '20–50 nhân sự', 'location' => 'Quận 3, TP.HCM', 'color' => '#F59E0B'],
        ['slug' => 'minh-duc-cloud', 'name' => 'Minh Đức Cloud', 'tagline' => 'Backend & API', 'size' => '50–200 nhân sự', 'location' => 'Thủ Đức, TP.HCM', 'color' => '#0EA5E9'],
        ['slug' => 'ngoc-lan-product', 'name' => 'Ngọc Lan Product', 'tagline' => 'Sản phẩm SaaS', 'size' => '50–200 nhân sự', 'location' => 'Quận 7, TP.HCM', 'color' => '#8B5CF6'],
        ['slug' => 'phuong-nam-data', 'name' => 'Phương Nam Data', 'tagline' => 'Dashboard cho SME', 'size' => '20–50 nhân sự', 'location' => 'Quận 1, TP.HCM', 'color' => '#10B981'],
        ['slug' => 'quang-minh-web', 'name' => 'Quang Minh Web', 'tagline' => 'Frontend studio', 'size' => '10–50 nhân sự', 'location' => 'Quận 4, TP.HCM', 'color' => '#3B82F6'],
        ['slug' => 'song-hong-digital', 'name' => 'Sông Hồng Digital', 'tagline' => 'Chuyển đổi số', 'size' => '200–500 nhân sự', 'location' => 'Hoàn Kiếm, Hà Nội', 'color' => '#DC2626'],
        ['slug' => 'thanh-tam-studio', 'name' => 'Thanh Tâm Studio', 'tagline' => 'UI cho app di động', 'size' => '10–50 nhân sự', 'location' => 'Hải Châu, Đà Nẵng', 'color' => '#EC4899'],
        ['slug' => 'uyen-phuong-ux', 'name' => 'Uyên Phương UX', 'tagline' => 'Nghiên cứu trải nghiệm', 'size' => '10–50 nhân sự', 'location' => 'Quận 1, TP.HCM', 'color' => '#F43F5E'],
        ['slug' => 'van-phat-tech', 'name' => 'Vạn Phát Tech', 'tagline' => 'Phần mềm bán lẻ', 'size' => '50–200 nhân sự', 'location' => 'Quận 5, TP.HCM', 'color' => '#059669'],
        ['slug' => 'xuan-mai-marketing', 'name' => 'Xuân Mai Marketing', 'tagline' => 'Social & ads', 'size' => '20–50 nhân sự', 'location' => 'Tân Bình, TP.HCM', 'color' => '#D946EF'],
        ['slug' => 'yen-nhi-creative', 'name' => 'Yến Nhi Creative', 'tagline' => 'Visual studio', 'size' => '10–50 nhân sự', 'location' => 'Phú Nhuận, TP.HCM', 'color' => '#FB7185'],
        ['slug' => 'anh-duong-labs', 'name' => 'Ánh Dương Labs', 'tagline' => 'Thí nghiệm sản phẩm', 'size' => '20–50 nhân sự', 'location' => 'Quận 2, TP.HCM', 'color' => '#F97316'],
        ['slug' => 'bao-ngoc-software', 'name' => 'Bảo Ngọc Software', 'tagline' => 'Laravel product team', 'size' => '50–200 nhân sự', 'location' => 'Quận 7, TP.HCM', 'color' => '#EF4444'],
        ['slug' => 'chau-a-tech', 'name' => 'Châu Á Tech', 'tagline' => 'Tích hợp hệ thống', 'size' => '200–500 nhân sự', 'location' => 'Đống Đa, Hà Nội', 'color' => '#1D4ED8'],
        ['slug' => 'dieu-linh-design', 'name' => 'Diệu Linh Design', 'tagline' => 'Thiết kế giao diện', 'size' => '10–50 nhân sự', 'location' => 'Quận 3, TP.HCM', 'color' => '#BE185D'],
        ['slug' => 'em-dem-social', 'name' => 'Êm Đềm Social', 'tagline' => 'Kênh TikTok & Instagram', 'size' => '10–50 nhân sự', 'location' => 'Bình Thạnh, TP.HCM', 'color' => '#7C3AED'],
        ['slug' => 'phuc-an-data', 'name' => 'Phúc An Data', 'tagline' => 'Báo cáo vận hành', 'size' => '20–50 nhân sự', 'location' => 'Quận 1, TP.HCM', 'color' => '#047857'],
        ['slug' => 'giao-linh-web', 'name' => 'Giao Linh Web', 'tagline' => 'Landing & dashboard', 'size' => '20–50 nhân sự', 'location' => 'Quận 10, TP.HCM', 'color' => '#0284C7'],
        ['slug' => 'huu-nghia-cloud', 'name' => 'Hữu Nghĩa Cloud', 'tagline' => 'API và hạ tầng', 'size' => '50–200 nhân sự', 'location' => 'Thủ Đức, TP.HCM', 'color' => '#0369A1'],
        ['slug' => 'ich-tam-media', 'name' => 'Ích Tâm Media', 'tagline' => 'Chiến dịch nội dung', 'size' => '20–50 nhân sự', 'location' => 'Sơn Trà, Đà Nẵng', 'color' => '#C026D3'],
        ['slug' => 'kieu-oanh-studio', 'name' => 'Kiều Oanh Studio', 'tagline' => 'Brand visual', 'size' => '10–50 nhân sự', 'location' => 'Quận 1, TP.HCM', 'color' => '#E11D48'],
        ['slug' => 'lam-phong-it', 'name' => 'Lâm Phong IT', 'tagline' => 'Đội ngũ full-stack', 'size' => '50–200 nhân sự', 'location' => 'Cầu Giấy, Hà Nội', 'color' => '#4338CA'],
        ['slug' => 'my-duyen-ux', 'name' => 'Mỹ Duyên UX', 'tagline' => 'Thiết kế trải nghiệm', 'size' => '10–50 nhân sự', 'location' => 'Quận 7, TP.HCM', 'color' => '#DB2777'],
        ['slug' => 'nam-khanh-code', 'name' => 'Nam Khánh Code', 'tagline' => 'Web app cho startup', 'size' => '20–50 nhân sự', 'location' => 'Quận 4, TP.HCM', 'color' => '#4F46E5'],
        ['slug' => 'oanh-kieu-ads', 'name' => 'Oanh Kiều Ads', 'tagline' => 'Quảng cáo số', 'size' => '20–50 nhân sự', 'location' => 'Quận 3, TP.HCM', 'color' => '#D97706'],
        ['slug' => 'phuong-thao-data', 'name' => 'Phương Thảo Data', 'tagline' => 'SQL và dashboard', 'size' => '20–50 nhân sự', 'location' => 'Quận 1, TP.HCM', 'color' => '#0F766E'],
        ['slug' => 'quynh-nhu-labs', 'name' => 'Quỳnh Như Labs', 'tagline' => 'Frontend product', 'size' => '20–50 nhân sự', 'location' => 'Hải Châu, Đà Nẵng', 'color' => '#2563EB'],
        ['slug' => 'son-tung-backend', 'name' => 'Sơn Tùng Backend', 'tagline' => 'Dịch vụ API', 'size' => '50–200 nhân sự', 'location' => 'Thủ Đức, TP.HCM', 'color' => '#1E40AF'],
        ['slug' => 'truc-lam-creative', 'name' => 'Trúc Lâm Creative', 'tagline' => 'Sáng tạo nội dung', 'size' => '10–50 nhân sự', 'location' => 'Quận 2, TP.HCM', 'color' => '#9333EA'],
    ];

    /** @var list<array{title: string, salary: string, type: string, hours: string, skills: list<string>, description: string, requirements: list<string>, benefits: list<string>, major: string, bio: string}> */
    private const POSITIONS = [
        [
            'title' => 'Frontend Developer',
            'salary' => '12–16 triệu/tháng',
            'type' => 'Part-time',
            'hours' => '3–5 giờ/ngày',
            'skills' => ['JavaScript', 'HTML/CSS', 'React', 'Git'],
            'description' => 'Làm giao diện web bằng JavaScript, HTML/CSS và React. Bạn nhận ticket nhỏ, được senior review trên Git.',
            'requirements' => ['Nắm JavaScript và HTML/CSS', 'Biết React hoặc sẵn sàng học', 'Dùng Git cơ bản', 'Làm được 3–5 giờ/ngày'],
            'benefits' => ['Mentor frontend', 'Lịch theo tuần học', 'Review code mỗi tuần'],
            'major' => 'Công nghệ thông tin',
            'bio' => 'Sinh viên thích lập trình giao diện, đã làm bài tập React và HTML/CSS, muốn đi làm part-time để viết JavaScript trên sản phẩm thật.',
        ],
        [
            'title' => 'Backend Developer',
            'salary' => '14–18 triệu/tháng',
            'type' => 'Part-time',
            'hours' => '4–6 giờ/ngày',
            'skills' => ['PHP', 'Laravel', 'SQL', 'API'],
            'description' => 'Viết API và sửa query SQL trên hệ thống Laravel. Ticket được giao vừa sức, có senior review.',
            'requirements' => ['Biết PHP và SQL', 'Đã dùng Laravel hoặc MVC', 'Hiểu REST API cơ bản', 'Cam kết ít nhất 4 giờ/ngày'],
            'benefits' => ['Mentor backend', 'Được đụng code sau onboarding', 'Lộ trình intern sang junior'],
            'major' => 'Khoa học máy tính',
            'bio' => 'Sinh viên làm backend với PHP, Laravel và SQL qua đồ án môn học, muốn thực tập viết API cho sản phẩm đang chạy.',
        ],
        [
            'title' => 'UI/UX Designer',
            'salary' => '9–13 triệu/tháng',
            'type' => 'Part-time',
            'hours' => '3–5 giờ/ngày',
            'skills' => ['Figma', 'Photoshop', 'UI/UX'],
            'description' => 'Vẽ wireframe và giao diện trên Figma, chỉnh asset bằng Photoshop. Làm việc trực tiếp với PM.',
            'requirements' => ['Dùng được Figma', 'Có cảm quan UI/UX', 'Photoshop cơ bản', 'Nhận feedback rõ ràng'],
            'benefits' => ['Mentor designer', 'Được đứng tên case study', 'Ca ngắn theo lịch học'],
            'major' => 'Thiết kế đồ họa',
            'bio' => 'Sinh viên thiết kế đồ họa, dùng Figma và Photoshop để làm UI/UX cho đồ án, muốn part-time tại studio.',
        ],
        [
            'title' => 'Data Analyst Intern',
            'salary' => '7–10 triệu/tháng',
            'type' => 'Thực tập',
            'hours' => '3–5 giờ/ngày',
            'skills' => ['SQL', 'Excel', 'Python', 'Dashboard'],
            'description' => 'Viết SQL, làm sạch số trên Excel và dựng dashboard đơn giản. Python là lợi thế khi cần xử lý file lớn.',
            'requirements' => ['SQL cơ bản gồm SELECT và JOIN', 'Excel thành thạo', 'Cẩn thận với số liệu', 'Python là lợi thế'],
            'benefits' => ['Dataset thật', 'Kèm analyst 4 tuần', 'Hybrid một vài buổi'],
            'major' => 'Hệ thống thông tin',
            'bio' => 'Sinh viên hệ thống thông tin, dùng SQL và Excel hằng tuần, đang học Python để làm dashboard cho báo cáo.',
        ],
        [
            'title' => 'Marketing Intern',
            'salary' => '5–8 triệu/tháng',
            'type' => 'Thực tập',
            'hours' => '4 giờ/ngày',
            'skills' => ['Marketing', 'Canva', 'TikTok', 'Copywriting'],
            'description' => 'Hỗ trợ marketing: lên lịch bài, viết caption và dựng ảnh Canva cho kênh TikTok.',
            'requirements' => ['Ham học marketing', 'Viết tiếng Việt rõ', 'Dùng Canva', 'Theo dõi TikTok thường xuyên'],
            'benefits' => ['Cầm mini-campaign sau tháng đầu', 'Workshop nội dung', 'Certificate sau 3 tháng'],
            'major' => 'Marketing',
            'bio' => 'Sinh viên marketing, viết content và dùng Canva để đăng TikTok cho câu lạc bộ, muốn thực tập digital marketing.',
        ],
    ];

    /** @var list<string> */
    private const STUDENT_NAMES = [
        'Nguyễn Minh Khang', 'Trần Gia Hân', 'Lê Hoàng Phúc', 'Phạm Thu Trang', 'Hoàng Đức Anh',
        'Võ Ngọc Mai', 'Đặng Quốc Bảo', 'Bùi Khánh Linh', 'Đỗ Thanh Tú', 'Ngô Hải Đăng',
        'Dương Mỹ Duyên', 'Lý Nhật Nam', 'Mai Phương Anh', 'Tô Quang Huy', 'Hồ Bảo Trân',
        'Trịnh Gia Bảo', 'Phan Hà My', 'Vũ Minh Triết', 'Đinh Ngọc Ánh', 'Lưu Thành Đạt',
        'Cao Thu Hà', 'Chu Đức Minh', 'Tạ Lan Chi', 'Kiều Hoàng Long', 'Ông Thanh Sơn',
        'La Gia Linh', 'Sử Nhật Quang', 'Ân Phương Thảo', 'Ứng Minh Tuấn', 'Từ Khánh Vy',
        'Quách Đức Trí', 'Mạc Thu Ngân', 'Tiêu Gia Huy', 'Doãn Hà Anh', 'Trương Minh Khoa',
        'Nông Bảo Châu', 'Thạch Lan Anh', 'Viên Đức Phát', 'Huỳnh Ngọc Trâm', 'Lâm Quốc Khánh',
    ];

    /** @var list<string> */
    private const SCHOOLS = [
        'Đại học Công Nghệ',
        'Đại học Bách Khoa',
        'Đại học Kinh tế',
        'Đại học Khoa học Tự nhiên',
        'Học viện Công nghệ Bưu chính Viễn thông',
    ];

    /** @var list<string> */
    private const YEARS = ['Sinh viên năm 2', 'Sinh viên năm 3', 'Sinh viên năm 4'];

    /** @var list<string> */
    private const STATUSES = ['pending', 'viewed', 'shortlisted', 'interview', 'pending'];

    public function run(): void
    {
        if (DB::table('companies')->where('slug', 'like', 'demo-%')->exists()) {
            $this->command?->warn('Đã có công ty demo-, bỏ qua BulkDemoSeeder.');

            return;
        }

        $now = now();
        $password = Hash::make('password');
        $skillIds = Skill::query()->pluck('id', 'name');

        DB::transaction(function () use ($now, $password, $skillIds): void {
            $companies = $this->insertCompanies($now, $password);
            $jobs = $this->insertJobs($companies, $skillIds, $now);
            $this->insertStudents($jobs, $skillIds, $now, $password);
        });

        app(ProfileScoreCalculator::class)->refreshAll();
        $students = app(RecommendationService::class)->refreshAll();
        $this->command?->info("Đã thêm 40 công ty, 200 tin và 40 sinh viên. Đã chấm lại {$students} hồ sơ.");
    }

    /**
     * @return list<int>
     */
    private function insertCompanies(mixed $now, string $password): array
    {
        $ids = [];

        foreach (self::COMPANIES as $index => $company) {
            $slug = 'demo-'.$company['slug'];
            $companyId = DB::table('companies')->insertGetId([
                'slug' => $slug,
                'name' => $company['name'],
                'verified' => $index % 4 !== 0,
                'tagline' => $company['tagline'],
                'size' => $company['size'],
                'location' => $company['location'],
                'color' => $company['color'],
                'initial' => mb_strtoupper(mb_substr($company['name'], 0, 1)),
                'about' => $company['name'].' tuyển sinh viên part-time và thực tập. '.$company['tagline'].'. Lịch làm việc xếp theo tuần học.',
                'rating' => 4.2 + ($index % 7) / 10,
                'reviews_count' => 12 + ($index * 3),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $userId = DB::table('users')->insertGetId([
                'name' => 'HR '.$company['name'],
                'email' => "hr@{$slug}.vn",
                'role' => 'employer',
                'is_active' => true,
                'password' => $password,
                'email_verified_at' => $now,
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

            $ids[] = $companyId;
        }

        return $ids;
    }

    /**
     * @param  list<int>  $companyIds
     * @param  Collection<string, int>  $skillIds
     * @return list<list<int>>
     */
    private function insertJobs(array $companyIds, $skillIds, mixed $now): array
    {
        $jobs = array_fill(0, count(self::POSITIONS), []);

        foreach ($companyIds as $companyIndex => $companyId) {
            $company = self::COMPANIES[$companyIndex];

            foreach (self::POSITIONS as $positionIndex => $position) {
                $jobId = DB::table('job_posts')->insertGetId([
                    'company_id' => $companyId,
                    'title' => $position['title'],
                    'salary' => $position['salary'],
                    'location' => $company['location'],
                    'is_remote' => $positionIndex === 4 && $companyIndex % 2 === 0,
                    'type' => $position['type'],
                    'hours' => $position['hours'],
                    'description' => $position['description'].' Làm việc tại '.$company['name'].'.',
                    'requirements' => json_encode($position['requirements'], JSON_UNESCAPED_UNICODE),
                    'benefits' => json_encode($position['benefits'], JSON_UNESCAPED_UNICODE),
                    'status' => 'open',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $last = array_key_last($position['skills']);
                foreach ($position['skills'] as $skillIndex => $name) {
                    DB::table('job_skill')->insert([
                        'job_post_id' => $jobId,
                        'skill_id' => $skillIds[$name],
                        'is_required' => $skillIndex !== $last,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $jobs[$positionIndex][] = $jobId;
            }
        }

        return $jobs;
    }

    /**
     * @param  list<list<int>>  $jobs
     * @param  Collection<string, int>  $skillIds
     */
    private function insertStudents(array $jobs, $skillIds, mixed $now, string $password): void
    {
        $parser = app(CvParser::class);
        $catalog = Skill::query()->get(['id', 'name', 'aliases'])
            ->map(fn (Skill $skill): array => [
                'id' => $skill->id,
                'name' => $skill->name,
                'aliases' => $skill->aliases ?? [],
            ]);

        foreach (self::STUDENT_NAMES as $index => $name) {
            $track = $index % count(self::POSITIONS);
            $position = self::POSITIONS[$track];
            $number = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
            $location = match ($index % 10) {
                0 => 'Hà Nội',
                1 => 'Đà Nẵng',
                default => 'TP. Hồ Chí Minh',
            };

            $userId = DB::table('users')->insertGetId([
                'name' => $name,
                'email' => "demo.sv{$number}@student.edu.vn",
                'role' => 'student',
                'is_active' => true,
                'password' => $password,
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $studentId = DB::table('students')->insertGetId([
                'user_id' => $userId,
                'school' => self::SCHOOLS[$index % count(self::SCHOOLS)],
                'major' => $position['major'],
                'year' => self::YEARS[$index % count(self::YEARS)],
                'location' => $location,
                'phone' => '09'.str_pad((string) (10000000 + $index), 8, '0', STR_PAD_LEFT),
                'bio' => $position['bio'],
                'profile_score' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($position['skills'] as $skillName) {
                DB::table('student_skill')->insert([
                    'student_id' => $studentId,
                    'skill_id' => $skillIds[$skillName],
                    'source' => 'cv',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $this->insertCv($parser, $catalog, $studentId, $name, $number, $position, $now);

            for ($offset = 0; $offset < 5; $offset++) {
                $companyIndex = ($index + ($offset * 8)) % count(self::COMPANIES);
                $created = $now->copy()->subDays(1 + (($index + $offset) % 18));

                DB::table('applications')->insert([
                    'student_id' => $studentId,
                    'job_post_id' => $jobs[$track][$companyIndex],
                    'status' => self::STATUSES[$offset],
                    'created_at' => $created,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * @param  iterable<array{id: int, name: string, aliases?: list<string>|null}>  $catalog
     * @param  array{title: string, skills: list<string>, major: string, bio: string}  $position
     */
    private function insertCv(CvParser $parser, iterable $catalog, int $studentId, string $name, string $number, array $position, mixed $now): void
    {
        $skillLine = implode(', ', $position['skills']);
        $text = implode("\n", [
            $name,
            'Email: demo.sv'.$number.'@student.edu.vn',
            'Điện thoại: 09'.str_pad((string) (10000000 + ((int) $number - 1)), 8, '0', STR_PAD_LEFT),
            'Đại học: sinh viên ngành '.$position['major'],
            $position['bio'],
            'Kỹ năng: '.$skillLine,
            'Kinh nghiệm thực tập và dự án môn học liên quan '.$position['title'].'.',
        ]);

        $path = "cvs/{$studentId}/".Str::lower(Str::random(40)).'.txt';
        Storage::disk('local')->put($path, $text);

        DB::table('cvs')->insert([
            'student_id' => $studentId,
            'original_name' => 'CV-'.Str::slug($name).'.txt',
            'path' => $path,
            'mime_type' => 'text/plain',
            'size' => strlen($text),
            'extracted_text' => $text,
            'parsed' => json_encode($parser->parse($text, $catalog), JSON_UNESCAPED_UNICODE),
            'parse_status' => 'parsed',
            'parse_error' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
