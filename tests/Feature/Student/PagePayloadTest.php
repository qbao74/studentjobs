<?php

namespace Tests\Feature\Student;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\Message;
use App\Models\Skill;
use App\Models\Student;
use App\Services\Frontend\JoblyPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagePayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_payload_has_only_public_data(): void
    {
        JobPost::factory()->create(['title' => 'Việc đang mở']);
        JobPost::factory()->hidden()->create(['title' => 'Việc bị ẩn']);

        $payload = app(JoblyPayload::class)->build(null);

        $this->assertFalse($payload['auth']['loggedIn']);
        $this->assertSame(['Việc đang mở'], array_column($payload['jobs'], 'title'));
        $this->assertNull($payload['jobs'][0]['match']);
        $this->assertFalse($payload['jobs'][0]['closed']);
        $this->assertNull($payload['user']);
        $this->assertSame([], $payload['skillOptions']);
    }

    public function test_student_payload_contains_real_profile_applications_and_scores(): void
    {
        $recruiter = Employer::factory()->create();
        $job = JobPost::factory()->for($recruiter->company)->create();
        $student = Student::factory()->create();
        $application = Application::factory()->for($student)->for($job)->create(['status' => ApplicationStatus::Interview]);
        Message::create(['application_id' => $application->id, 'user_id' => $recruiter->user_id, 'body' => 'Mời phỏng vấn']);

        $payload = app(JoblyPayload::class)->build($student->user);

        $this->assertSame($student->user->name, $payload['user']['name']);
        $this->assertSame(1, $payload['user']['stats']['applied']);
        $this->assertSame(1, $payload['user']['stats']['interviewed']);
        $this->assertIsInt($payload['jobs'][0]['match']);
        $this->assertSame('current', collect($payload['applications'][0]['steps'])->firstWhere('key', 'interview')['status']);
        $this->assertSame('Mời phỏng vấn', $payload['conversations'][0]['last']);
        $this->assertSame(1, $payload['unread']);
        $this->assertSame(Skill::orderBy('name')->pluck('name')->all(), $payload['skillOptions']);
    }

    public function test_closed_job_still_shown_for_its_applicant(): void
    {
        $student = Student::factory()->create();
        $job = JobPost::factory()->closed()->create();
        Application::factory()->for($student)->for($job)->create();

        $payload = app(JoblyPayload::class)->build($student->user);

        $shown = collect($payload['jobs'])->firstWhere('id', $job->id);
        $this->assertNotNull($shown);
        $this->assertTrue($shown['closed'], 'Giao diện dựa vào cờ closed để khóa nút ứng tuyển.');
    }

    public function test_pages_render_payload_and_employers_are_redirected(): void
    {
        $this->get('/')->assertOk()->assertSee('window.JOBLY', false)->assertSee('Chào bạn!');

        $student = Student::factory()->create();
        $student->user->update(['name' => 'Trần Thị Hoa']);
        $this->actingAs($student->user)->get('/')->assertOk()->assertSee('Chào Hoa!');
        auth()->logout();

        $employer = Employer::factory()->create();
        $this->actingAs($employer->user)->get('/explore')->assertRedirect('/employer');
    }

    public function test_chat_page_has_no_placeholder_controls(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($student->user)->get('/chat')
            ->assertOk()
            ->assertDontSee('Gọi thoại')
            ->assertDontSee('company.html')
            ->assertDontSee('quick-replies');
    }

    public function test_user_text_is_escaped_inside_script_payload(): void
    {
        JobPost::factory()->create(['title' => '</script><script>alert(1)</script>']);

        $this->get('/')->assertDontSee('</script><script>alert(1)', false);
    }
}
