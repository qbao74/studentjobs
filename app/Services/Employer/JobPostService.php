<?php

namespace App\Services\Employer;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\JobPost;
use App\Models\Skill;
use App\Services\Matching\RecommendationService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Tạo / sửa / đóng / xoá tin của nhà tuyển dụng.
 * Mỗi lần nội dung hoặc trạng thái tin đổi, điểm khớp với mọi sinh viên được tính lại (refreshForJob).
 */
class JobPostService
{
    public function __construct(private RecommendationService $recommendations) {}

    /**
     * @param  array{fields: array<string, mixed>, skills: array<string, bool>}  $data  skills: tên => bắt buộc?
     */
    public function create(Company $company, array $data): JobPost
    {
        $job = DB::transaction(function () use ($company, $data) {
            $job = $company->jobPosts()->create([...$data['fields'], 'status' => JobStatus::Open]);
            $this->syncSkills($job, $data['skills']);

            return $job;
        });

        $this->recommendations->refreshForJob($job);

        return $job;
    }

    /**
     * @param  array{fields: array<string, mixed>, skills: array<string, bool>}  $data
     */
    public function update(JobPost $job, array $data): JobPost
    {
        DB::transaction(function () use ($job, $data) {
            $job->update($data['fields']);
            $this->syncSkills($job, $data['skills']);
        });

        $this->recommendations->refreshForJob($job->refresh());

        return $job;
    }

    /** Nhà tuyển dụng chỉ đổi qua lại Mở ↔ Đóng; trạng thái Ẩn do admin quyết định. */
    public function setStatus(JobPost $job, JobStatus $status): void
    {
        if ($status === JobStatus::Hidden) {
            throw new UnprocessableEntityHttpException('Chỉ quản trị viên mới ẩn được tin.');
        }

        $job->update(['status' => $status]);
        $this->recommendations->refreshForJob($job);
    }

    /** Tin đã có người nộp thì không xoá (mất đơn và tin nhắn của sinh viên) — chỉ cho đóng. */
    public function delete(JobPost $job): void
    {
        if ($job->applications()->exists()) {
            throw new UnprocessableEntityHttpException('Tin đã có người ứng tuyển nên không thể xoá. Hãy đóng tin thay vì xoá.');
        }

        $job->delete();
    }

    /** @param  array<string, bool>  $skills */
    private function syncSkills(JobPost $job, array $skills): void
    {
        // Hai tên khác hoa thường có thể trỏ cùng một kỹ năng → gộp, ưu tiên "bắt buộc".
        $pivot = [];
        foreach ($skills as $name => $required) {
            $id = Skill::findOrCreateByName((string) $name)->id;
            $pivot[$id] = ['is_required' => ($pivot[$id]['is_required'] ?? false) || $required];
        }

        $job->skills()->sync($pivot);
    }
}
