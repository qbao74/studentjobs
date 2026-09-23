<?php

namespace App\Services\Student;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\JobPost;
use App\Models\Student;
use Illuminate\Database\UniqueConstraintViolationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ApplicationService
{
    public function apply(Student $student, JobPost $job): Application
    {
        if ($job->status !== JobStatus::Open) {
            throw new UnprocessableEntityHttpException('Tin này đã ngừng nhận hồ sơ.');
        }

        if ($student->applications()->where('job_post_id', $job->id)->exists()) {
            throw new ConflictHttpException('Bạn đã ứng tuyển việc này rồi.');
        }

        try {
            return $student->applications()->create([
                'job_post_id' => $job->id,
                'status' => ApplicationStatus::Pending,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Hai request cùng lúc (bấm đúp): request sau chạm unique index.
            throw new ConflictHttpException('Bạn đã ứng tuyển việc này rồi.');
        }
    }

    /** Chỉ rút được khi nhà tuyển dụng chưa đưa vào vòng trong. */
    public function withdraw(Application $application): void
    {
        if ($application->status->step() > ApplicationStatus::Viewed->step() || $application->status->isFinal()) {
            throw new UnprocessableEntityHttpException('Đơn đã được xử lý, không thể rút.');
        }

        $application->delete();
    }
}
