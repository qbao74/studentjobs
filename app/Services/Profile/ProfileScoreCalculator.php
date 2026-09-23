<?php

namespace App\Services\Profile;

use App\Models\Student;

/**
 * Mức đầy đủ hồ sơ (0–100). Khác với điểm khớp việc:
 * điểm này chỉ nói hồ sơ đã điền đủ chưa, không nói hợp việc nào.
 */
class ProfileScoreCalculator
{
    /** @var array<string, array{weight: int, label: string}> */
    private const CRITERIA = [
        'school' => ['weight' => 10, 'label' => 'Thêm trường học'],
        'major' => ['weight' => 15, 'label' => 'Thêm ngành học'],
        'year' => ['weight' => 5, 'label' => 'Thêm năm học'],
        'location' => ['weight' => 10, 'label' => 'Thêm khu vực sinh sống'],
        'phone' => ['weight' => 5, 'label' => 'Thêm số điện thoại'],
        'bio' => ['weight' => 15, 'label' => 'Viết giới thiệu bản thân (từ 30 ký tự)'],
        'skills' => ['weight' => 20, 'label' => 'Chọn ít nhất 3 kỹ năng'],
        'cv' => ['weight' => 20, 'label' => 'Tải CV đọc được chữ (PDF/DOCX)'],
    ];

    /**
     * @return array{score: int, missing: list<string>}
     */
    public function evaluate(Student $student): array
    {
        $student->loadMissing(['skills', 'cv']);

        $done = [
            'school' => filled($student->school),
            'major' => filled($student->major),
            'year' => filled($student->year),
            'location' => filled($student->location),
            'phone' => filled($student->phone),
            'bio' => mb_strlen(trim((string) $student->bio)) >= 30,
            'skills' => $student->skills->count() >= 3,
            'cv' => $student->cv?->parse_status === 'parsed',
        ];

        $score = 0;
        $missing = [];

        foreach (self::CRITERIA as $key => $criterion) {
            if ($done[$key]) {
                $score += $criterion['weight'];
            } else {
                $missing[] = $criterion['label'];
            }
        }

        return ['score' => $score, 'missing' => $missing];
    }

    public function refresh(Student $student): int
    {
        $score = $this->evaluate($student)['score'];
        $student->forceFill(['profile_score' => $score])->save();

        return $score;
    }
}
