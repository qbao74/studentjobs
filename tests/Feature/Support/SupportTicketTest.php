<?php

namespace Tests\Feature\Support;

use App\Enums\SupportCategory;
use App\Enums\SupportTicketStatus;
use App\Models\Student;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_support(): void
    {
        $this->get(route('support.index'))->assertRedirect(route('login'));
    }

    public function test_user_creates_ticket_and_receives_admin_reply_notification(): void
    {
        $student = Student::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($student->user)
            ->post(route('support.store'), [
                'category' => SupportCategory::Payment->value,
                'subject' => 'Không thấy gói thanh toán',
                'body' => 'Mình đã vào trang thanh toán nhưng không thấy nút xác nhận.',
            ])
            ->assertRedirect();

        $ticket = SupportTicket::query()->firstOrFail();
        $this->assertSame($student->user_id, $ticket->user_id);
        $this->assertSame(SupportTicketStatus::Open, $ticket->status);

        $this->actingAs($student->user)
            ->get(route('support.show', $ticket))
            ->assertOk()
            ->assertSee('Không thấy gói thanh toán');

        $this->actingAs(Student::factory()->create()->user)
            ->get(route('support.show', $ticket))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.support.reply', $ticket), ['body' => 'Bạn vào mục Thanh toán trên menu nhà tuyển dụng.'])
            ->assertRedirect();

        $this->assertSame(SupportTicketStatus::Answered, $ticket->fresh()->status);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $student->user_id,
            'related_id' => $ticket->id,
        ]);
    }

    public function test_user_cannot_spoof_admin_role_from_form(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->post(route('support.store'), [
                'category' => SupportCategory::Account->value,
                'subject' => 'Khóa nhầm tài khoản',
                'body' => 'Nhờ kiểm tra giúp tài khoản bị khóa.',
                'role' => 'admin',
                'user_id' => 999,
            ])
            ->assertRedirect();

        $ticket = SupportTicket::query()->firstOrFail();
        $this->assertSame($student->user_id, $ticket->user_id);
        $this->actingAs($student->user)->get(route('admin.support.index'))->assertForbidden();
    }

    public function test_validation_rejects_short_content(): void
    {
        $student = Student::factory()->create();

        $this->actingAs($student->user)
            ->post(route('support.store'), [
                'category' => SupportCategory::Other->value,
                'subject' => 'Hi',
                'body' => 'Ngắn',
            ])
            ->assertSessionHasErrors(['subject', 'body']);
    }
}
