<?php

namespace Tests\Unit;

use App\Services\Matching\JobRequirements;
use App\Services\Matching\MatchScorer;
use App\Services\Matching\StudentFeatures;
use Tests\TestCase;

class MatchScorerTest extends TestCase
{
    private function job(array $overrides = []): JobRequirements
    {
        return new JobRequirements(...$overrides + [
            'jobId' => 1,
            'requiredSkills' => [1 => 'PHP', 2 => 'SQL', 3 => 'Laravel'],
            'optionalSkills' => [4 => 'Git'],
            'keywords' => ['backend', 'api', 'laravel', 'rest', 'database', 'dat', 'lich', 'ung', 'dung', 'server'],
            'fields' => ['it'],
            'city' => 'hcm',
            'isRemote' => false,
        ]);
    }

    private function student(array $overrides = []): StudentFeatures
    {
        return new StudentFeatures(...$overrides + [
            'studentId' => 1,
            'skills' => [1 => 'PHP', 2 => 'SQL', 3 => 'Laravel', 4 => 'Git'],
            'keywords' => ['backend', 'api', 'laravel', 'rest'],
            'fields' => ['it'],
            'city' => 'hcm',
            'hasCv' => true,
        ]);
    }

    public function test_perfect_candidate_scores_100(): void
    {
        $result = (new MatchScorer)->score($this->student(), $this->job());

        $this->assertSame(100, $result->score);
        $this->assertSame([], $result->missingRequired);
    }

    public function test_required_skill_counts_double(): void
    {
        // Thiếu Laravel (bắt buộc, trọng số 2) trong tổng 2+2+2+1 = 7 → 5/7 = 71.
        $withoutRequired = (new MatchScorer)->score($this->student(['skills' => [1 => 'PHP', 2 => 'SQL', 4 => 'Git']]), $this->job());
        // Thiếu Git (điểm cộng, trọng số 1) → 6/7 = 86.
        $withoutOptional = (new MatchScorer)->score($this->student(['skills' => [1 => 'PHP', 2 => 'SQL', 3 => 'Laravel']]), $this->job());

        $this->assertSame(71, $withoutRequired->criterion('skills')['score']);
        $this->assertSame(86, $withoutOptional->criterion('skills')['score']);
        $this->assertSame(['Laravel'], $withoutRequired->missingRequired);
        $this->assertSame(['Git'], $withoutOptional->missingOptional);
    }

    public function test_total_is_weighted_average_of_criteria(): void
    {
        // skills 71×60, keywords 100×20, field 100×10, location 0×10 → (4260+2000+1000)/100 = 72.6 → 73
        $result = (new MatchScorer)->score(
            $this->student(['skills' => [1 => 'PHP', 2 => 'SQL', 4 => 'Git'], 'city' => 'hn']),
            $this->job(),
        );

        $this->assertSame(0, $result->criterion('location')['score']);
        $this->assertSame(73, $result->score);
    }

    public function test_missing_data_criteria_are_skipped_not_penalised(): void
    {
        // Tin không có ngành, sinh viên không có thành phố → chỉ tính skills + keywords.
        $result = (new MatchScorer)->score($this->student(['city' => null]), $this->job(['fields' => []]));

        $this->assertFalse($result->criterion('field')['applicable']);
        $this->assertFalse($result->criterion('location')['applicable']);
        $this->assertSame(100, $result->score);
    }

    public function test_remote_job_always_matches_location(): void
    {
        $result = (new MatchScorer)->score($this->student(['city' => 'hn']), $this->job(['isRemote' => true]));

        $this->assertSame(100, $result->criterion('location')['score']);
    }

    public function test_empty_profile_scores_low_but_not_crash(): void
    {
        $result = (new MatchScorer)->score(
            $this->student(['skills' => [], 'keywords' => [], 'fields' => [], 'city' => null, 'hasCv' => false]),
            $this->job(),
        );

        $this->assertSame(0, $result->score);
        $this->assertCount(3, $result->missingRequired);
    }

    public function test_job_without_any_data_scores_zero(): void
    {
        $result = (new MatchScorer)->score($this->student(['city' => null]), $this->job([
            'requiredSkills' => [], 'optionalSkills' => [], 'keywords' => [], 'fields' => [], 'city' => null,
        ]));

        $this->assertSame(0, $result->score);
    }
}
