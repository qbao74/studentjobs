<?php

namespace Tests\Feature\Employer;

use App\Models\Application;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\RecruiterNote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruiterNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_employer_can_crud_own_notes(): void
    {
        [$employer, $application] = $this->inbox();

        $this->actingAs($employer->user)
            ->post(route('employer.notes.store', $application), ['body' => 'Giao tiếp tốt, hỏi thêm lịch học.'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $note = RecruiterNote::query()->firstOrFail();
        $this->assertSame($employer->id, $note->employer_id);

        $this->actingAs($employer->user)
            ->get(route('employer.applications.show', $application))
            ->assertOk()
            ->assertSee('Giao tiếp tốt');

        $this->actingAs($employer->user)
            ->patch(route('employer.notes.update', $note), ['body' => 'Đã gọi điện, hẹn thứ Năm.'])
            ->assertRedirect();

        $this->assertSame('Đã gọi điện, hẹn thứ Năm.', $note->fresh()->body);

        $this->actingAs($employer->user)
            ->delete(route('employer.notes.destroy', $note))
            ->assertRedirect();

        $this->assertModelMissing($note);
    }

    public function test_other_employer_and_student_cannot_access_notes(): void
    {
        [$employer, $application] = $this->inbox();
        $note = RecruiterNote::factory()->create([
            'application_id' => $application->id,
            'employer_id' => $employer->id,
            'body' => 'Ghi chú bí mật',
        ]);

        $other = Employer::factory()->create();
        $this->actingAs($other->user)
            ->get(route('employer.applications.show', $application))
            ->assertForbidden();
        $this->actingAs($other->user)
            ->patch(route('employer.notes.update', $note), ['body' => 'Sửa chui'])
            ->assertForbidden();

        $this->actingAs($application->student->user)
            ->get(route('employer.applications.show', $application))
            ->assertForbidden();
        $this->actingAs($application->student->user)
            ->post(route('employer.notes.store', $application), ['body' => 'Sinh viên không được ghi chú'])
            ->assertForbidden();

        $this->assertSame('Ghi chú bí mật', $note->fresh()->body);
    }

    public function test_empty_note_is_rejected(): void
    {
        [$employer, $application] = $this->inbox();

        $this->actingAs($employer->user)
            ->post(route('employer.notes.store', $application), ['body' => '  '])
            ->assertSessionHasErrors('body');
    }

    /** @return array{0: Employer, 1: Application} */
    private function inbox(): array
    {
        $employer = Employer::factory()->create();
        $application = Application::factory()->for(JobPost::factory()->for($employer->company))->create();

        return [$employer, $application];
    }
}
