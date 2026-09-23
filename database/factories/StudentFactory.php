<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'school' => 'Đại học Bách Khoa',
            'major' => 'Công nghệ thông tin',
            'year' => 'Sinh viên năm 3',
            'location' => 'TP. Hồ Chí Minh',
            'phone' => fake()->numerify('09## ### ###'),
            'bio' => 'Sinh viên thích lập trình web, muốn tìm việc part-time.',
        ];
    }
}
