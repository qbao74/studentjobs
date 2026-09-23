<?php

namespace Tests\Feature\Student;

use App\Models\Company;
use App\Models\JobPost;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookmarkApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_job_toggles(): void
    {
        $student = Student::factory()->create();
        $job = JobPost::factory()->create();

        $this->actingAs($student->user)->postJson(route('api.jobs.save', $job))->assertJson(['saved' => true]);
        $this->assertTrue($student->savedJobs()->whereKey($job->id)->exists());

        $this->actingAs($student->user)->postJson(route('api.jobs.save', $job))->assertJson(['saved' => false]);
        $this->assertSame(0, $student->savedJobs()->count());
    }

    public function test_follow_company_by_slug_and_count_followers(): void
    {
        $student = Student::factory()->create();
        $company = Company::factory()->create(['slug' => 'techwind']);

        $this->actingAs($student->user)
            ->postJson('/api/companies/techwind/follow')
            ->assertJson(['following' => true, 'followers' => 1]);

        $this->actingAs($student->user)
            ->postJson('/api/companies/techwind/follow')
            ->assertJson(['following' => false, 'followers' => 0]);

        $this->assertNotNull($company);
    }

    public function test_unknown_company_returns_404_json(): void
    {
        $this->actingAs(Student::factory()->create()->user)
            ->postJson('/api/companies/khong-ton-tai/follow')
            ->assertNotFound();
    }

    public function test_closed_job_can_be_unsaved_but_not_saved(): void
    {
        $student = Student::factory()->create();
        $closed = JobPost::factory()->closed()->create();

        $this->actingAs($student->user)->postJson(route('api.jobs.save', $closed))->assertUnprocessable();

        $student->savedJobs()->attach($closed->id);
        $this->actingAs($student->user)->postJson(route('api.jobs.save', $closed))->assertJson(['saved' => false]);
    }

    public function test_guest_cannot_bookmark(): void
    {
        $this->postJson(route('api.jobs.save', JobPost::factory()->create()))->assertUnauthorized();
    }
}
