<?php

namespace Database\Factories;

use App\Models\ServicePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicePlan>
 */
class ServicePlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'plan-'.fake()->unique()->slug(2),
            'name' => 'Gói thử',
            'description' => 'Gói dùng trong kiểm thử.',
            'price_amount' => 99000,
            'duration_days' => 30,
            'max_open_jobs' => 2,
            'is_active' => true,
        ];
    }
}
