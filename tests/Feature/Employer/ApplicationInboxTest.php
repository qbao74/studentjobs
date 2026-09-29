<?php

namespace Tests\Feature\Employer;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\Message;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_employer_sees_only_applications_for_their_company(): void
    {
        [$employer, $application] = $this->applicationForEmployer();
        $other = Application::factory()->create();

        $this->actingAs($employer->user)
            ->get(route('employer.applications.index'))
            ->assertOk()
            ->assertSee($application->student->user->name)
            ->assertDontSee($other->student->user->name);

        $this->actingAs($employer->user)
            ->get(route('employer.applications.show', $other))
            ->assertForbidden();
    }

    public function test_opening_a_pending_application_marks_it_viewed(): void
    {
        [$employer, $application] = $this->applicationForEmployer();

        $this->actingAs($employer->user)
            ->get(route('employer.applications.show', $application))
            ->assertOk()
            ->assertSee($application->student->major);

        $this->assertSame(ApplicationStatus::Viewed, $application->fresh()->status);
    }

    public function test_employer_can_move_an_application_and_reply(): void
    {
        [$employer, $application] = $this->applicationForEmployer();

        $this->actingAs($employer->user)
            ->patch(route('employer.applications.status', $application), ['status' => 'interview'])
            ->assertRedirect();

        $this->assertSame(ApplicationStatus::Interview, $application->fresh()->status);

        $this->actingAs($employer->user)
            ->post(route('employer.applications.messages.store', $application), ['body' => 'Mời bạn phỏng vấn lúc 9h.'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('messages', [
            'application_id' => $application->id,
            'user_id' => $employer->user_id,
            'body' => 'Mời bạn phỏng vấn lúc 9h.',
        ]);
    }

    public function test_chat_lists_the_company_conversations(): void
    {
        [$employer, $application] = $this->applicationForEmployer();
        Message::factory()->create([
            'application_id' => $application->id,
            'user_id' => $application->student->user_id,
            'body' => 'Em xin chào ạ.',
        ]);

        $this->actingAs($employer->user)
            ->get(route('employer.chat'))
            ->assertOk()
            ->assertSee($application->student->user->name)
            ->assertSee('Em xin chào ạ.');

        $this->actingAs($employer->user)
            ->get(route('employer.chat', ['c' => $application->id]))
            ->assertOk()
            ->assertSee('Em xin chào ạ.');
    }

    public function test_student_cannot_open_the_employer_inbox(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($student->user)->get(route('employer.applications.index'))->assertForbidden();
        $this->actingAs($student->user)->get(route('employer.chat'))->assertForbidden();
    }

    /** @return array{0: Employer, 1: Application} */
    private function applicationForEmployer(): array
    {
        $employer = Employer::factory()->create();
        $job = JobPost::factory()->create(['company_id' => $employer->company_id]);
        $student = Student::factory()->create(['major' => 'Công nghệ thông tin']);
        $application = Application::factory()->create([
            'student_id' => $student->id,
            'job_post_id' => $job->id,
        ]);

        return [$employer, $application->load('student.user')];
    }
}
