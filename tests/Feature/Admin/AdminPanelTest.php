<?php

namespace Tests\Feature\Admin;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Company;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_open_the_admin_area(): void
    {
        $this->get('/admin')->assertRedirect('/login');

        $this->actingAs(Student::factory()->create()->user)->get('/admin')->assertForbidden();
        $this->actingAs(Employer::factory()->create()->user)->get('/admin')->assertForbidden();

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Quản trị Jobly');
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee($admin->email);
        $this->actingAs($admin)->get(route('admin.companies.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.jobs.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.applications.index'))->assertOk();
    }

    public function test_admin_can_lock_another_account_but_not_themselves(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.active', $admin), ['is_active' => '0'])
            ->assertSessionHasErrors('user');
        $this->assertTrue($admin->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.users.active', $student->user), ['is_active' => '0'])
            ->assertSessionHas('status');
        $this->assertFalse($student->user->fresh()->is_active);

        $this->actingAs($admin)
            ->patch(route('admin.users.active', $student->user), ['is_active' => '1'])
            ->assertSessionHas('status');
        $this->assertTrue($student->user->fresh()->is_active);
    }

    public function test_admin_verifies_a_company_and_hides_a_job(): void
    {
        $admin = User::factory()->admin()->create();
        $employer = Employer::factory()->create();
        $company = $employer->company;
        $this->assertFalse($company->verified);

        $this->actingAs($admin)
            ->patch(route('admin.companies.verified', $company), ['verified' => '1'])
            ->assertSessionHas('status');
        $this->assertTrue($company->fresh()->verified);

        $job = JobPost::factory()->for($company)->create();

        $this->actingAs($admin)
            ->patch(route('admin.jobs.status', $job), ['status' => 'hidden'])
            ->assertSessionHas('status');
        $this->assertSame(JobStatus::Hidden, $job->fresh()->status);

        $this->actingAs($employer->user)
            ->patch(route('employer.jobs.status', $job), ['status' => 'open'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.jobs.status', $job), ['status' => 'open'])
            ->assertSessionHas('status');
        $this->assertSame(JobStatus::Open, $job->fresh()->status);
    }

    public function test_admin_can_read_an_application_without_joining_the_chat(): void
    {
        $admin = User::factory()->admin()->create();
        $application = Application::factory()->create();
        Message::factory()->create([
            'application_id' => $application->id,
            'user_id' => $application->student->user_id,
            'body' => 'Em nộp hồ sơ ạ.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.applications.show', $application))
            ->assertOk()
            ->assertSee($application->student->user->name)
            ->assertSee('Em nộp hồ sơ ạ.')
            ->assertDontSee('Nhắn cho ứng viên');

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => $application->student->user->email]))
            ->assertOk()
            ->assertSee($application->student->user->name);
    }

    public function test_dashboard_stats_link_to_the_filtered_lists(): void
    {
        $admin = User::factory()->admin()->create();
        Company::factory()->create(['verified' => false]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.companies.index', ['verified' => '0']), false)
            ->assertSee(route('admin.jobs.index', ['status' => 'open']), false)
            ->assertSee(route('admin.applications.index', ['status' => 'pending']), false);
    }
}
