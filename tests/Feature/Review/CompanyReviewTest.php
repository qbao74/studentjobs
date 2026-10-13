<?php

namespace Tests\Feature\Review;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_read_reviews_but_cannot_submit(): void
    {
        $company = Company::factory()->create();

        $this->get(route('companies.reviews.index', $company))->assertOk();
        $this->post(route('companies.reviews.store', $company), [
            'rating' => 5,
            'comment' => 'Rất tốt cho sinh viên.',
        ])->assertRedirect(route('login'));
    }

    public function test_hired_student_can_review_once(): void
    {
        [$student, $company] = $this->hiredStudent();

        $this->actingAs($student->user)
            ->post(route('companies.reviews.store', $company), [
                'rating' => 5,
                'comment' => 'Mentor nhiệt tình, lịch học xếp được.',
            ])
            ->assertRedirect(route('companies.reviews.index', $company));

        $this->assertDatabaseHas('company_reviews', [
            'student_id' => $student->id,
            'company_id' => $company->id,
            'rating' => 5,
        ]);
        $this->assertSame(1, $company->fresh()->reviews_count);
        $this->assertEquals(5.0, $company->fresh()->rating);

        $this->actingAs($student->user)
            ->post(route('companies.reviews.store', $company), [
                'rating' => 4,
                'comment' => 'Đánh giá lần hai sẽ bị từ chối.',
            ])
            ->assertSessionHasErrors('comment');

        $this->assertSame(1, CompanyReview::query()->count());
    }

    public function test_student_cannot_review_without_hired_application(): void
    {
        $student = Student::factory()->create();
        $company = Company::factory()->create();
        Application::factory()->create([
            'student_id' => $student->id,
            'job_post_id' => JobPost::factory()->for($company)->create()->id,
            'status' => ApplicationStatus::Interview,
        ]);

        $this->actingAs($student->user)
            ->post(route('companies.reviews.store', $company), [
                'rating' => 5,
                'comment' => 'Chưa trúng tuyển vẫn gửi.',
            ])
            ->assertSessionHasErrors('comment');

        $this->assertSame(0, CompanyReview::query()->count());
    }

    public function test_client_cannot_force_rating_outside_range_or_wrong_company(): void
    {
        [$student, $company] = $this->hiredStudent();
        $other = Company::factory()->create();

        $this->actingAs($student->user)
            ->post(route('companies.reviews.store', $company), [
                'rating' => 9,
                'comment' => 'Điểm ngoài thang.',
            ])
            ->assertSessionHasErrors('rating');

        $this->actingAs($student->user)
            ->post(route('companies.reviews.store', $other), [
                'rating' => 5,
                'comment' => 'Công ty không liên quan đến đơn trúng tuyển.',
            ])
            ->assertSessionHasErrors('comment');
    }

    public function test_employer_cannot_edit_a_student_review(): void
    {
        [$student, $company] = $this->hiredStudent();
        $review = CompanyReview::factory()->create([
            'student_id' => $student->id,
            'company_id' => $company->id,
            'application_id' => $student->applications()->first()->id,
        ]);
        $employer = Employer::factory()->create(['company_id' => $company->id]);

        $this->assertFalse($employer->user->can('update', $review));
        $this->assertFalse($employer->user->can('delete', $review));
    }

    /** @return array{0: Student, 1: Company} */
    private function hiredStudent(): array
    {
        $company = Company::factory()->create();
        $student = Student::factory()->create();
        Application::factory()->create([
            'student_id' => $student->id,
            'job_post_id' => JobPost::factory()->for($company)->create()->id,
            'status' => ApplicationStatus::Hired,
        ]);

        return [$student, $company];
    }
}
