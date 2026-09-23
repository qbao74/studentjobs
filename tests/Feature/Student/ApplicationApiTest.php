<?php

namespace Tests\Feature\Student;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_gets_401_json_so_the_login_popup_can_open(): void
    {
        $job = JobPost::factory()->create();

        $this->postJson(route('api.jobs.apply', $job))->assertUnauthorized();
    }

    public function test_employer_cannot_apply(): void
    {
        $job = JobPost::factory()->create();

        $this->actingAs(Employer::factory()->create()->user)
            ->postJson(route('api.jobs.apply', $job))
            ->assertForbidden();
    }

    public function test_student_applies_once(): void
    {
        $student = Student::factory()->create();
        $job = JobPost::factory()->create();

        $this->actingAs($student->user)
            ->postJson(route('api.jobs.apply', $job))
            ->assertCreated()
            ->assertJsonPath('application.jobId', $job->id)
            ->assertJsonPath('application.status', 'pending');

        $this->actingAs($student->user)
            ->postJson(route('api.jobs.apply', $job))
            ->assertStatus(409)
            ->assertJsonPath('message', 'Bạn đã ứng tuyển việc này rồi.');

        $this->assertSame(1, Application::count());
    }

    public function test_cannot_apply_to_closed_job(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->postJson(route('api.jobs.apply', JobPost::factory()->closed()->create()))
            ->assertUnprocessable();
    }

    public function test_student_withdraws_own_pending_application(): void
    {
        $application = Application::factory()->create();

        $this->actingAs($application->student->user)
            ->deleteJson(route('api.applications.destroy', $application))
            ->assertOk();

        $this->assertModelMissing($application);
    }

    public function test_cannot_withdraw_after_shortlist_or_someone_elses(): void
    {
        $application = Application::factory()->create(['status' => ApplicationStatus::Shortlisted]);

        $this->actingAs($application->student->user)
            ->deleteJson(route('api.applications.destroy', $application))
            ->assertUnprocessable();

        $this->actingAs(Student::factory()->create()->user)
            ->deleteJson(route('api.applications.destroy', $application))
            ->assertForbidden();

        $this->assertModelExists($application);
    }

    public function test_csrf_is_required_for_session_api(): void
    {
        $student = Student::factory()->create();
        $job = JobPost::factory()->create();

        // Tắt bỏ qua CSRF mặc định của môi trường test để kiểm tra đúng hành vi thật.
        $this->withMiddleware()->actingAs($student->user);
        $this->app['env'] = 'local';

        $this->postJson(route('api.jobs.apply', $job))->assertStatus(419);
    }
}
