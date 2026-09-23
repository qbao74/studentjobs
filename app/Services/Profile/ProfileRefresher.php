<?php

namespace App\Services\Profile;

use App\Models\Student;
use App\Services\Matching\RecommendationService;

/** Gọi sau mọi thay đổi hồ sơ sinh viên để các số liệu suy ra không bị cũ. */
class ProfileRefresher
{
    public function __construct(
        private ProfileScoreCalculator $profileScore,
        private RecommendationService $recommendations,
    ) {}

    public function refresh(Student $student): void
    {
        $student = $student->fresh(['skills', 'cv']);

        $this->profileScore->refresh($student);
        $this->recommendations->refreshForStudent($student);
    }
}
