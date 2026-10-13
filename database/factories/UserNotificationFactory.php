<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotification>
 */
class UserNotificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => NotificationType::ApplicationStatusChanged,
            'title' => 'Cập nhật đơn ứng tuyển',
            'body' => 'Nhà tuyển dụng đã cập nhật trạng thái đơn của bạn.',
            'link' => '/applications',
            'dedup_key' => 'test-'.fake()->unique()->uuid(),
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => ['read_at' => now()]);
    }
}
