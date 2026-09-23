<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Models\Company;
use App\Models\JobPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobPost>
 */
class JobPostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->randomElement(['Frontend Developer', 'Backend Developer', 'UI/UX Designer', 'Data Analyst Intern']),
            'salary' => '8–12 triệu/tháng',
            'location' => 'Quận 1, TP.HCM',
            'is_remote' => false,
            'type' => 'Part-time',
            'hours' => '4 giờ/ngày',
            'description' => fake()->sentence(15),
            'requirements' => ['Chịu học hỏi', 'Làm việc nhóm tốt'],
            'benefits' => ['Lịch linh hoạt'],
            'status' => JobStatus::Open,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => JobStatus::Closed]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => ['status' => JobStatus::Hidden]);
    }
}
