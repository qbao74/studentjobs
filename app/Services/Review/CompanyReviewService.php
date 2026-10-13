<?php

namespace App\Services\Review;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\Student;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CompanyReviewService
{
    public function eligibleApplication(Student $student, Company $company): ?Application
    {
        return $student->applications()
            ->where('status', ApplicationStatus::Hired)
            ->whereHas('jobPost', fn ($q) => $q->where('company_id', $company->id))
            ->latest()
            ->first();
    }

    public function canReview(Student $student, Company $company): bool
    {
        if ($student->reviews()->where('company_id', $company->id)->exists()) {
            return false;
        }

        return $this->eligibleApplication($student, $company) !== null;
    }

    public function create(Student $student, Company $company, int $rating, string $comment): CompanyReview
    {
        if ($rating < CompanyReview::MIN_RATING || $rating > CompanyReview::MAX_RATING) {
            throw new UnprocessableEntityHttpException('Điểm đánh giá phải từ '.CompanyReview::MIN_RATING.' đến '.CompanyReview::MAX_RATING.'.');
        }

        $application = $this->eligibleApplication($student, $company);
        if ($application === null) {
            throw new UnprocessableEntityHttpException('Bạn chỉ được đánh giá công ty sau khi đơn ứng tuyển đã được xác nhận trúng tuyển.');
        }

        if ($student->reviews()->where('company_id', $company->id)->exists()) {
            throw new ConflictHttpException('Bạn đã đánh giá công ty này rồi.');
        }

        try {
            return DB::transaction(function () use ($student, $company, $application, $rating, $comment) {
                $review = CompanyReview::query()->create([
                    'company_id' => $company->id,
                    'student_id' => $student->id,
                    'application_id' => $application->id,
                    'rating' => $rating,
                    'comment' => $comment,
                ]);

                $this->refreshCompanyStats($company);

                return $review;
            });
        } catch (UniqueConstraintViolationException) {
            throw new ConflictHttpException('Bạn đã đánh giá công ty này rồi.');
        }
    }

    public function refreshCompanyStats(Company $company): void
    {
        $count = $company->reviews()->count();
        $avg = $company->reviews()->avg('rating');

        $company->forceFill([
            'reviews_count' => $count,
            'rating' => $count > 0 ? round((float) $avg, 1) : null,
        ])->save();
    }
}
