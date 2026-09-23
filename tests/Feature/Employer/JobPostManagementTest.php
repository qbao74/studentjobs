<?php

namespace Tests\Feature\Employer;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\JobRecommendation;
use App\Models\Skill;
use App\Models\Student;
use App\Services\Matching\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobPostManagementTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Backend Developer',
            'type' => 'Part-time',
            'salary' => '10–14 triệu/tháng',
            'location' => 'Quận 3, TP.HCM',
            'is_remote' => '0',
            'hours' => '4 giờ/ngày',
            'description' => 'Xây API cho ứng dụng đặt lịch.',
            'requirements' => "- Biết PHP\n- Biết SQL\n\n",
            'benefits' => 'Mentor 1:1',
            'required_skills' => 'PHP, laravel',
            'optional_skills' => 'Docker, php',
        ], $overrides);
    }

    public function test_only_employers_can_open_the_employer_area(): void
    {
        $this->get('/employer')->assertRedirect('/login');

        $student = Student::factory()->create();
        $this->actingAs($student->user)->get('/employer')->assertForbidden();

        $employer = Employer::factory()->create();
        $this->actingAs($employer->user)->get('/employer')->assertOk()->assertSee($employer->company->name);
    }

    public function test_employer_creates_job_with_skills_and_students_get_scores(): void
    {
        Skill::create(['name' => 'Laravel', 'aliases' => []]);
        $student = Student::factory()->create();
        $employer = Employer::factory()->create();

        $this->actingAs($employer->user)
            ->post('/employer/jobs', $this->validData())
            ->assertRedirect('/employer/jobs')
            ->assertSessionHas('status');

        $job = JobPost::where('title', 'Backend Developer')->firstOrFail();
        $this->assertSame($employer->company_id, $job->company_id);
        $this->assertSame(JobStatus::Open, $job->status);
        $this->assertSame(['Biết PHP', 'Biết SQL'], $job->requirements);

        $skills = $job->skills->mapWithKeys(fn ($s) => [$s->name => (bool) $s->pivot->is_required])->all();
        // "laravel" dùng lại kỹ năng "Laravel" có sẵn; "php" ở ô điểm cộng trùng PHP bắt buộc → vẫn bắt buộc.
        $this->assertEquals(['PHP' => true, 'Laravel' => true, 'Docker' => false], $skills);
        $this->assertSame(1, Skill::where('name', 'Laravel')->count());

        $this->assertTrue(JobRecommendation::where('student_id', $student->id)->where('job_post_id', $job->id)->exists());
    }

    public function test_job_needs_skills_and_a_location_unless_remote(): void
    {
        $employer = Employer::factory()->create();

        $this->actingAs($employer->user)
            ->post('/employer/jobs', $this->validData(['required_skills' => '', 'optional_skills' => ' , ', 'location' => '']))
            ->assertSessionHasErrors(['required_skills', 'location']);

        $this->actingAs($employer->user)
            ->post('/employer/jobs', $this->validData(['location' => '', 'is_remote' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Remote', JobPost::firstOrFail()->location);
    }

    public function test_employer_cannot_touch_another_companys_job(): void
    {
        $job = JobPost::factory()->create();
        $other = Employer::factory()->create();

        $this->actingAs($other->user)->get("/employer/jobs/{$job->id}/edit")->assertForbidden();
        $this->actingAs($other->user)->put("/employer/jobs/{$job->id}", $this->validData())->assertForbidden();
        $this->actingAs($other->user)->patch("/employer/jobs/{$job->id}/status", ['status' => 'closed'])->assertForbidden();
        $this->actingAs($other->user)->delete("/employer/jobs/{$job->id}")->assertForbidden();

        $this->assertSame(JobStatus::Open, $job->fresh()->status);
    }

    public function test_create_and_edit_forms_render(): void
    {
        $employer = Employer::factory()->create();
        $job = JobPost::factory()->for($employer->company)->create(['title' => 'Tin cần sửa']);
        $job->skills()->attach(Skill::create(['name' => 'Figma', 'aliases' => []]), ['is_required' => true]);

        $this->actingAs($employer->user)->get('/employer/jobs/create')->assertOk()->assertSee('Đăng tin mới');
        $this->actingAs($employer->user)->get("/employer/jobs/{$job->id}/edit")
            ->assertOk()
            ->assertSee('Sửa tin tuyển dụng')
            ->assertSee('value="Tin cần sửa"', false)
            ->assertSee('Figma');
    }

    public function test_updating_job_resyncs_skills(): void
    {
        $employer = Employer::factory()->create();
        $job = JobPost::factory()->for($employer->company)->create();
        $job->skills()->attach(Skill::create(['name' => 'Figma', 'aliases' => []]), ['is_required' => true]);

        $this->actingAs($employer->user)
            ->put("/employer/jobs/{$job->id}", $this->validData(['title' => 'Backend Intern', 'optional_skills' => '']))
            ->assertRedirect('/employer/jobs');

        $job->refresh();
        $this->assertSame('Backend Intern', $job->title);
        $this->assertEqualsCanonicalizing(['PHP', 'laravel'], $job->skills->pluck('name')->all());
    }

    public function test_closing_job_removes_scores_and_hidden_job_is_locked(): void
    {
        $employer = Employer::factory()->create();
        $job = JobPost::factory()->for($employer->company)->create();
        Student::factory()->create();
        app(RecommendationService::class)->refreshForJob($job);
        $this->assertTrue($job->recommendations()->exists());

        $this->actingAs($employer->user)->patch("/employer/jobs/{$job->id}/status", ['status' => 'closed'])->assertSessionHas('status');
        $this->assertSame(JobStatus::Closed, $job->fresh()->status);
        $this->assertFalse($job->recommendations()->exists());

        $this->actingAs($employer->user)->patch("/employer/jobs/{$job->id}/status", ['status' => 'hidden'])->assertSessionHasErrors('status');

        $job->update(['status' => JobStatus::Hidden]);
        $this->actingAs($employer->user)->patch("/employer/jobs/{$job->id}/status", ['status' => 'open'])->assertForbidden();
        $this->actingAs($employer->user)->get("/employer/jobs/{$job->id}/edit")->assertForbidden();
    }

    public function test_job_with_applications_cannot_be_deleted(): void
    {
        $employer = Employer::factory()->create();
        $withApplicant = JobPost::factory()->for($employer->company)->create();
        Application::factory()->for($withApplicant)->create();
        $empty = JobPost::factory()->for($employer->company)->create();

        $this->actingAs($employer->user)->delete("/employer/jobs/{$withApplicant->id}")->assertSessionHasErrors('job');
        $this->assertModelExists($withApplicant);

        $this->actingAs($employer->user)->delete("/employer/jobs/{$empty->id}")->assertRedirect('/employer/jobs');
        $this->assertModelMissing($empty);
    }

    public function test_job_list_only_shows_own_company_and_filters_by_status(): void
    {
        $employer = Employer::factory()->create();
        JobPost::factory()->for($employer->company)->create(['title' => 'Tin đang mở của tôi']);
        JobPost::factory()->for($employer->company)->closed()->create(['title' => 'Tin đã đóng của tôi']);
        JobPost::factory()->create(['title' => 'Tin công ty khác']);

        $this->actingAs($employer->user)->get('/employer/jobs')
            ->assertOk()
            ->assertSee('Tin đang mở của tôi')
            ->assertSee('Tin đã đóng của tôi')
            ->assertDontSee('Tin công ty khác');

        $this->actingAs($employer->user)->get('/employer/jobs?status=closed')
            ->assertSee('Tin đã đóng của tôi')
            ->assertDontSee('Tin đang mở của tôi');
    }
}
