<?php

namespace Database\Factories;

use App\Enums\SupportCategory;
use App\Enums\SupportTicketStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category' => SupportCategory::Product,
            'subject' => 'Không xem được tin tuyển dụng',
            'status' => SupportTicketStatus::Open,
        ];
    }
}
