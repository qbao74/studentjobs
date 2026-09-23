<?php

namespace Tests\Feature\Auth;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Cv;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_browse_public_pages(): void
    {
        foreach (['/', '/explore', '/jobs', '/companies'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_guest_is_sent_to_login_for_personal_pages(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_employer_cannot_open_student_pages(): void
    {
        $employer = Employer::factory()->create();

        $this->actingAs($employer->user)->get('/profile')->assertForbidden();
    }

    public function test_only_owning_employer_can_update_job(): void
    {
        $owner = Employer::factory()->create();
        $other = Employer::factory()->create();
        $job = JobPost::factory()->for($owner->company)->create();

        $this->assertTrue($owner->user->can('update', $job));
        $this->assertFalse($other->user->can('update', $job));
    }

    public function test_hidden_job_cannot_be_reopened_by_employer(): void
    {
        $owner = Employer::factory()->create();
        $job = JobPost::factory()->for($owner->company)->hidden()->create();

        $this->assertFalse($owner->user->can('update', $job));
        $this->assertFalse(User::factory()->make()->can('view', $job));
    }

    public function test_application_visibility(): void
    {
        $recruiter = Employer::factory()->create();
        $job = JobPost::factory()->for($recruiter->company)->create();
        $application = Application::factory()->for($job)->create();
        $stranger = Student::factory()->create();

        $this->assertTrue($application->student->user->can('view', $application));
        $this->assertTrue($recruiter->user->can('updateStatus', $application));
        $this->assertFalse($stranger->user->can('view', $application));
        $this->assertFalse(Employer::factory()->create()->user->can('view', $application));

        $application->update(['status' => ApplicationStatus::Hired]);
        $this->assertFalse($recruiter->user->can('updateStatus', $application->fresh()));
    }

    public function test_employer_only_downloads_cv_of_own_applicants(): void
    {
        $recruiter = Employer::factory()->create();
        $student = Student::factory()->create();
        $cv = Cv::create(['student_id' => $student->id, 'original_name' => 'cv.pdf', 'path' => 'cvs/x.pdf']);

        $this->assertFalse($recruiter->user->can('download', $cv));

        Application::factory()->for($student)->for(JobPost::factory()->for($recruiter->company))->create();

        $this->assertTrue($recruiter->user->can('download', $cv));
        $this->assertTrue($student->user->can('download', $cv));
        $this->assertFalse(Student::factory()->create()->user->can('download', $cv));
    }
}
