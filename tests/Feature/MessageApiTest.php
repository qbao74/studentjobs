<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\Message;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageApiTest extends TestCase
{
    use RefreshDatabase;

    private Application $application;

    private Employer $recruiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recruiter = Employer::factory()->create();
        $this->application = Application::factory()->for(JobPost::factory()->for($this->recruiter->company))->create();
    }

    public function test_both_parties_can_chat_and_see_direction(): void
    {
        $student = $this->application->student->user;

        $this->actingAs($student)
            ->postJson(route('api.messages.store', $this->application), ['body' => '  Chào anh chị  '])
            ->assertCreated()
            ->assertJsonPath('message.text', 'Chào anh chị')
            ->assertJsonPath('message.from', 'user');

        $this->actingAs($this->recruiter->user)
            ->getJson(route('api.messages.index', $this->application))
            ->assertOk()
            ->assertJsonPath('messages.0.from', 'recruiter');
    }

    public function test_reading_marks_other_party_messages_as_read(): void
    {
        $message = Message::create(['application_id' => $this->application->id, 'user_id' => $this->recruiter->user_id, 'body' => 'Hi']);

        $this->actingAs($this->application->student->user)->getJson(route('api.messages.index', $this->application));

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_after_parameter_returns_only_new_messages(): void
    {
        $first = Message::create(['application_id' => $this->application->id, 'user_id' => $this->recruiter->user_id, 'body' => 'Một']);
        Message::create(['application_id' => $this->application->id, 'user_id' => $this->recruiter->user_id, 'body' => 'Hai']);

        $this->actingAs($this->application->student->user)
            ->getJson(route('api.messages.index', $this->application).'?after='.$first->id)
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.text', 'Hai');
    }

    public function test_outsiders_cannot_read_or_write(): void
    {
        foreach ([Student::factory()->create()->user, Employer::factory()->create()->user, User::factory()->admin()->create()] as $outsider) {
            $this->actingAs($outsider)->getJson(route('api.messages.index', $this->application))->assertForbidden();
            $this->actingAs($outsider)->postJson(route('api.messages.store', $this->application), ['body' => 'x'])->assertForbidden();
        }
    }

    public function test_empty_or_too_long_message_is_rejected(): void
    {
        $student = $this->application->student->user;

        $this->actingAs($student)->postJson(route('api.messages.store', $this->application), ['body' => '   '])->assertUnprocessable();
        $this->actingAs($student)->postJson(route('api.messages.store', $this->application), ['body' => str_repeat('a', 2001)])->assertUnprocessable();
    }

    public function test_html_is_stored_as_plain_text(): void
    {
        $this->actingAs($this->application->student->user)
            ->postJson(route('api.messages.store', $this->application), ['body' => '<img src=x onerror=alert(1)>'])
            ->assertJsonPath('message.text', '<img src=x onerror=alert(1)>');
    }
}
