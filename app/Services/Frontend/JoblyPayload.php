<?php

namespace App\Services\Frontend;

use App\Enums\ApplicationStatus;
use App\Enums\Role;
use App\Models\Company;
use App\Models\JobPost;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use App\Services\Matching\RecommendationService;
use App\Services\Profile\ProfileScoreCalculator;
use Illuminate\Support\Collection;

/**
 * Dữ liệu cho các trang sinh viên, in vào window.JOBLY trong layout.
 * Khách chỉ nhận dữ liệu công khai (tin, công ty); sinh viên nhận thêm hồ sơ, đơn, hội thoại, điểm khớp.
 */
class JoblyPayload
{
    public function __construct(
        private StudentPresenter $presenter,
        private RecommendationService $recommendations,
        private ProfileScoreCalculator $profileScore,
    ) {}

    public function build(?User $user): array
    {
        $student = $user?->hasRole(Role::Student) ? $user->student : null;

        $companies = Company::query()
            ->withCount(['jobPosts as open_jobs_count' => fn ($q) => $q->open(), 'followers'])
            ->orderBy('id')
            ->get();

        $jobs = JobPost::open()->with(['company', 'skills'])->latest()->get();

        $payload = [
            'auth' => [
                'loggedIn' => $user !== null,
                'role' => $user?->role->value,
                'name' => $user?->name,
            ],
            'companies' => $companies->values()->mapWithKeys(
                fn (Company $c, int $i) => [$c->slug => $this->presenter->company($c, $i)]
            )->all(),
            'jobs' => [],
            'user' => null,
            'applications' => [],
            'conversations' => [],
            'saved' => [],
            'following' => [],
            'unread' => 0,
        ];

        if (! $student) {
            $payload['jobs'] = $jobs->map(fn (JobPost $job) => $this->presenter->job($job))->all();

            return $payload;
        }

        return array_merge($payload, $this->forStudent($user, $student, $jobs));
    }

    private function forStudent(User $user, Student $student, Collection $jobs): array
    {
        $student->load(['skills', 'cv']);

        // Tài khoản mới đăng ký chưa có điểm khớp: tính một lần ngay lúc mở trang.
        if ($jobs->isNotEmpty() && $student->recommendations()->doesntExist()) {
            $this->recommendations->refreshForStudent($student);
        }

        $recs = $student->recommendations()->get()->keyBy('job_post_id');
        $applications = $student->applications()
            ->with(['jobPost.company', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount(['messages as unread_count' => fn ($q) => $q->whereNull('read_at')->where('user_id', '!=', $user->id)])
            ->latest()
            ->get();

        // Tin đã đóng nhưng sinh viên từng ứng tuyển vẫn phải hiện trong trang tiến trình.
        $appliedClosed = JobPost::with(['company', 'skills'])
            ->whereIn('id', $applications->pluck('job_post_id'))
            ->whereNotIn('id', $jobs->modelKeys())
            ->get();

        $allJobs = $jobs->concat($appliedClosed);

        $scores = $recs->pluck('score');
        $stats = [
            'applied' => $applications->count(),
            'interviewed' => $applications->filter(fn ($a) => $a->status->step() >= ApplicationStatus::Interview->step())->count(),
            'hired' => $applications->where('status', ApplicationStatus::Hired)->count(),
            'avgMatch' => $scores->isEmpty() ? 0 : (int) round($scores->sortDesc()->take(5)->avg()),
        ];

        return [
            'jobs' => $allJobs->map(fn (JobPost $job) => $this->presenter->job($job, $recs->get($job->id)))->all(),
            'user' => $this->presenter->user($user, $student, $this->profileScore->evaluate($student)['missing'], $stats),
            'applications' => $applications->map(fn ($a) => $this->presenter->application($a))->all(),
            'conversations' => $applications->map(fn ($a) => $this->conversation($a))->all(),
            'saved' => $student->savedJobs()->pluck('job_posts.id')->all(),
            'following' => $student->followedCompanies()->pluck('companies.slug')->all(),
            'unread' => (int) $applications->sum('unread_count'),
        ];
    }

    private function conversation($application): array
    {
        /** @var Message|null $last */
        $last = $application->messages->first();

        return [
            'id' => $application->id,
            'companyId' => $application->jobPost->company->slug,
            'jobId' => $application->job_post_id,
            'last' => $last?->body ?? 'Chưa có tin nhắn. Hãy chào nhà tuyển dụng!',
            'time' => $last ? $this->shortTime($last->created_at) : '',
            'unread' => (int) $application->unread_count,
        ];
    }

    private function shortTime($time): string
    {
        return match (true) {
            $time->isToday() => $time->format('H:i'),
            $time->isYesterday() => 'Hôm qua',
            $time->greaterThan(now()->subWeek()) => $time->translatedFormat('l'),
            default => $time->format('d/m'),
        };
    }
}
