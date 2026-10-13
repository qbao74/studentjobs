<?php

namespace App\Services\Student;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Enums\NotificationType;
use App\Models\Application;
use App\Models\JobPost;
use App\Models\Student;
use App\Services\Notification\UserNotificationService;
use Illuminate\Database\UniqueConstraintViolationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ApplicationService
{
    public function __construct(private UserNotificationService $notifications) {}

    public function apply(Student $student, JobPost $job): Application
    {
        $job->loadMissing('company');

        if ($job->status !== JobStatus::Open || ! $job->company->hasActiveSubscription()) {
            throw new UnprocessableEntityHttpException('Tin này đã ngừng nhận hồ sơ.');
        }

        if ($student->applications()->where('job_post_id', $job->id)->exists()) {
            throw new ConflictHttpException('Bạn đã ứng tuyển việc này rồi.');
        }

        try {
            $application = $student->applications()->create([
                'job_post_id' => $job->id,
                'status' => ApplicationStatus::Pending,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Hai request cùng lúc (bấm đúp): request sau chạm unique index.
            throw new ConflictHttpException('Bạn đã ứng tuyển việc này rồi.');
        }

        $job->loadMissing('company.employers.user', 'company');
        foreach ($job->company->employers as $employer) {
            $this->notifications->notify(
                $employer->user,
                NotificationType::ApplicationReceived,
                'application:'.$application->id.':received:'.$employer->user_id,
                [
                    'title' => 'Đơn ứng tuyển mới',
                    'body' => $student->user->name.' đã ứng tuyển vị trí '.$job->title.'.',
                    'link' => route('employer.applications.show', $application),
                ],
                $application,
            );
        }

        return $application;
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
