<?php

namespace Tests\Unit;

use App\Services\Matching\JobRequirements;
use App\Services\Matching\MatchExplainer;
use App\Services\Matching\MatchScorer;
use App\Services\Matching\StudentFeatures;
use Tests\TestCase;

class MatchExplainerTest extends TestCase
{
    private function explain(array $studentSkills, bool $hasCv = true): array
    {
        $job = new JobRequirements(1, [1 => 'PHP', 2 => 'SQL'], [3 => 'Git'], ['backend', 'api'], ['it'], 'hcm', false);
        $student = new StudentFeatures(1, $studentSkills, ['backend'], ['it'], 'hcm', $hasCv);

        $result = (new MatchScorer)->score($student, $job);

        return (new MatchExplainer)->explain($result, $student) + ['score' => $result->score];
    }

    public function test_pros_and_cons_name_the_actual_skills(): void
    {
        $e = $this->explain([1 => 'PHP']);

        $this->assertContains('Có kỹ năng bắt buộc: PHP.', $e['pros']);
        $this->assertContains('Thiếu kỹ năng bắt buộc: SQL.', $e['cons']);
        $this->assertContains('Chưa có kỹ năng điểm cộng: Git.', $e['cons']);
        $this->assertStringContainsString('Nên học thêm SQL', $e['comment']);
    }

    public function test_comment_states_level_and_score(): void
    {
        $e = $this->explain([1 => 'PHP', 2 => 'SQL', 3 => 'Git']);

        $this->assertSame('Rất phù hợp', $e['level']);
        $this->assertStringStartsWith("Rất phù hợp ({$e['score']}%).", $e['comment']);
        $this->assertStringContainsString('đủ kỹ năng', $e['comment']);
    }

    public function test_suggests_uploading_cv_when_missing(): void
    {
        $this->assertStringContainsString('Tải CV', $this->explain([1 => 'PHP'], hasCv: false)['comment']);
    }

    public function test_levels_follow_thresholds(): void
    {
        $explainer = new MatchExplainer;

        $this->assertSame('Rất phù hợp', $explainer->level(80));
        $this->assertSame('Khá phù hợp', $explainer->level(79));
        $this->assertSame('Phù hợp một phần', $explainer->level(40));
        $this->assertSame('Chưa phù hợp', $explainer->level(39));
    }
}
