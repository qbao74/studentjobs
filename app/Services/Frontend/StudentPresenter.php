<?php

namespace App\Services\Frontend;

use App\Models\Application;
use App\Models\Company;
use App\Models\JobPost;
use App\Models\JobRecommendation;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Đổi model thành mảng đúng cấu trúc mà public/jobly/js đang dùng (trước đây là data.js).
 * Chỉ định dạng dữ liệu, không truy vấn thêm — quan hệ cần dùng phải được nạp sẵn.
 */
class StudentPresenter
{
    private const DEFAULT_COVERS = [
        'https://images.unsplash.com/photo-1497215728101-856f4ea42174?auto=format&fit=crop&w=1600&q=80',
        'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=1600&q=80',
        'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=1600&q=80',
    ];

    private const DEFAULT_JOB_IMAGE = 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=1200&q=80';

    public function job(JobPost $job, ?JobRecommendation $rec = null): array
    {
        return [
            'id' => $job->id,
            'title' => $job->title,
            'companyId' => $job->company->slug,
            'company' => $job->company->name,
            'salary' => $job->salary ?? 'Thỏa thuận',
            'location' => $job->location ?? '',
            'remote' => $job->is_remote,
            'type' => $job->type ?? '',
            'hours' => $job->hours ?? '',
            'skills' => $job->skills->pluck('name')->all(),
            'requiredSkills' => $job->skills->where('pivot.is_required', true)->pluck('name')->values()->all(),
            'image' => $job->image ?: self::DEFAULT_JOB_IMAGE,
            'description' => $job->description ?? '',
            'requirements' => $job->requirements ?? [],
            'benefits' => $job->benefits ?? [],
            'postedAt' => $job->created_at?->toDateString(),
            'match' => $rec?->score,
            'whyMatch' => $rec ? [
                'pros' => $rec->pros ?? [],
                'cons' => $rec->cons ?? [],
                'comment' => $rec->comment,
                'breakdown' => $rec->breakdown ?? [],
            ] : null,
        ];
    }

    public function company(Company $company, int $index = 0): array
    {
        return [
            'id' => $company->slug,
            'name' => $company->name,
            'verified' => $company->verified,
            'tagline' => $company->tagline ?? '',
            'size' => $company->size ?? '',
            'location' => $company->location ?? '',
            'color' => $company->color ?? '#6366f1',
            'initial' => $company->initial ?: Str::upper(Str::substr(Str::ascii($company->name), 0, 1)),
            'cover' => self::DEFAULT_COVERS[$index % count(self::DEFAULT_COVERS)],
            'about' => $company->about ?? '',
            'rating' => $company->rating,
            'reviews' => $company->reviews_count,
            'jobsCount' => (int) ($company->open_jobs_count ?? 0),
            'followers' => $this->compactNumber((int) ($company->followers_count ?? 0)),
        ];
    }

    public function application(Application $application): array
    {
        return [
            'id' => $application->id,
            'jobId' => $application->job_post_id,
            'appliedAt' => $application->created_at->toDateString(),
            'status' => $application->status->value,
            'statusLabel' => $application->status->label(),
            'canWithdraw' => ! $application->status->isFinal() && $application->status->step() <= 2,
            'steps' => $application->status->timeline(),
        ];
    }

    public function message(Message $message, int $viewerId): array
    {
        return [
            'id' => $message->id,
            'from' => $message->user_id === $viewerId ? 'user' : 'recruiter',
            'text' => $message->body,
            'time' => $message->created_at->format('H:i'),
            'date' => $message->created_at->toDateString(),
        ];
    }

    public function user(User $user, Student $student, array $profileMissing, array $stats): array
    {
        $cv = $student->cv;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'firstName' => Str::afterLast(trim($user->name), ' '),
            'email' => $user->email,
            'year' => $student->year ?? '',
            'school' => $student->school ?? '',
            'major' => $student->major ?? '',
            'location' => $student->location ?? '',
            'phone' => $student->phone ?? '',
            'bio' => $student->bio ?? '',
            'avatar' => $this->avatarUrl($student),
            'skills' => $student->skills->pluck('name')->all(),
            'skillsDetail' => $student->skills->map(fn ($s) => ['name' => $s->name, 'source' => $s->pivot->source])->all(),
            'cv' => $cv ? [
                'name' => $cv->original_name,
                'updated' => $cv->updated_at->diffForHumans(),
                'status' => $cv->parse_status,
                'error' => $cv->parse_error,
                'size' => $cv->size,
                'foundSkills' => array_column($cv->parsed['skills'] ?? [], 'name'),
            ] : null,
            'profileScore' => $student->profile_score,
            'profileMissing' => $profileMissing,
            'stats' => $stats,
        ];
    }

    public function avatarUrl(Student $student): ?string
    {
        if (! $student->avatar) {
            return null;
        }

        return Str::startsWith($student->avatar, ['http://', 'https://'])
            ? $student->avatar
            : asset('storage/'.$student->avatar);
    }

    private function compactNumber(int $n): string
    {
        return $n >= 1000 ? rtrim(rtrim(number_format($n / 1000, 1, ',', ''), '0'), ',').'k' : (string) $n;
    }
}
