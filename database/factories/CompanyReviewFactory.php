<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\CompanyReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyReview>
 */
class CompanyReviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $application = Application::factory()->create(['status' => ApplicationStatus::Hired]);

        return [
            'company_id' => $application->jobPost->company_id,
            'student_id' => $application->student_id,
            'application_id' => $application->id,
            'rating' => 5,
            'comment' => 'Môi trường làm việc tốt, mentor nhiệt tình.',
        ];
    }
}
