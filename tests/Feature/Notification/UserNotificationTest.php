<?php

namespace Tests\Feature\Notification;

use App\Enums\NotificationType;
use App\Models\Application;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\Student;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_notifications(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_status_change_notifies_student_once(): void
    {
        $employer = Employer::factory()->create();
        $application = Application::factory()->for(JobPost::factory()->for($employer->company))->create();

        $this->actingAs($employer->user)
            ->patch(route('employer.applications.status', $application), ['status' => 'hired'])
            ->assertRedirect();

        $this->assertSame(1, UserNotification::query()->where('user_id', $application->student->user_id)->count());

        $this->actingAs($employer->user)
            ->patch(route('employer.applications.status', $application), ['status' => 'hired'])
            ->assertForbidden();

        $this->assertSame(1, UserNotification::query()->where('user_id', $application->student->user_id)->count());
        $this->assertSame(NotificationType::ApplicationStatusChanged, UserNotification::query()->first()->type);
    }

    public function test_user_only_reads_own_notification(): void
    {
        $mine = UserNotification::factory()->create();
        $theirs = UserNotification::factory()->create();

        $this->actingAs($mine->user)
            ->get(route('notifications.read', $theirs))
            ->assertForbidden();

        $this->actingAs($mine->user)
            ->get(route('notifications.read', $mine))
            ->assertRedirect();

        $this->assertNotNull($mine->fresh()->read_at);
        $this->assertNull($theirs->fresh()->read_at);
    }

    public function test_mark_all_read_only_affects_current_user(): void
    {
        $user = Student::factory()->create()->user;
        $mine = UserNotification::factory()->create(['user_id' => $user->id]);
        $theirs = UserNotification::factory()->create();

        $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertNotNull($mine->fresh()->read_at);
        $this->assertNull($theirs->fresh()->read_at);
    }

    public function test_applying_notifies_employer(): void
    {
        $employer = Employer::factory()->create();
        $job = JobPost::factory()->for($employer->company)->create();
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->postJson(route('api.jobs.apply', $job))
            ->assertCreated();

        $this->assertTrue(
            UserNotification::query()
                ->where('user_id', $employer->user_id)
                ->where('type', NotificationType::ApplicationReceived)
                ->exists()
        );
    }
}
