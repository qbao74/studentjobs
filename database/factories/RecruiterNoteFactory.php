<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Employer;
use App\Models\JobPost;
use App\Models\RecruiterNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecruiterNote>
 */
class RecruiterNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $employer = Employer::factory()->create();
        $application = Application::factory()->for(JobPost::factory()->for($employer->company))->create();

        return [
            'application_id' => $application->id,
            'employer_id' => $employer->id,
            'body' => 'Ứng viên giao tiếp tốt, cần hỏi thêm về lịch học.',
        ];
    }
}
