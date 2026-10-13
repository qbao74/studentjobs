<?php

namespace Database\Factories;

use App\Models\SupportReply;
use App\Models\SupportTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportReply>
 */
class SupportReplyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ticket = SupportTicket::factory()->create();

        return [
            'support_ticket_id' => $ticket->id,
            'user_id' => $ticket->user_id,
            'body' => 'Mình cần hỗ trợ thêm về vấn đề này.',
        ];
    }
}
