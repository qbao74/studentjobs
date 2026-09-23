<?php

namespace App\Services\Matching;

/**
 * Bước MATCHING + SCORING. Hàm thuần: cùng đầu vào luôn ra cùng điểm, không đọc database.
 * Mỗi tiêu chí cho điểm 0–100; tổng = trung bình có trọng số của các tiêu chí áp dụng được.
 */
class MatchScorer
{
    public function score(StudentFeatures $student, JobRequirements $job): MatchResult
    {
        $weights = config('matching.weights');

        [$skills, $skillEvidence] = $this->skills($student, $job);
        [$keywords, $matchedKeywords] = $this->keywords($student, $job);

        $breakdown = [
            ['key' => 'skills', 'label' => 'Kỹ năng', 'weight' => $weights['skills']] + $skills,
            ['key' => 'keywords', 'label' => 'Kinh nghiệm & mô tả', 'weight' => $weights['keywords']] + $keywords,
            ['key' => 'field', 'label' => 'Ngành học', 'weight' => $weights['field']] + $this->field($student, $job),
            ['key' => 'location', 'label' => 'Địa điểm', 'weight' => $weights['location']] + $this->location($student, $job),
        ];

        return new MatchResult(
            score: $this->total($breakdown),
            breakdown: $breakdown,
            matchedRequired: $skillEvidence['matchedRequired'],
            missingRequired: $skillEvidence['missingRequired'],
            matchedOptional: $skillEvidence['matchedOptional'],
            missingOptional: $skillEvidence['missingOptional'],
            matchedKeywords: $matchedKeywords,
        );
    }

    private function skills(StudentFeatures $student, JobRequirements $job): array
    {
        $has = fn (int $id) => array_key_exists($id, $student->skills);

        $evidence = [
            'matchedRequired' => array_values(array_filter($job->requiredSkills, $has, ARRAY_FILTER_USE_KEY)),
            'missingRequired' => array_values(array_filter($job->requiredSkills, fn ($id) => ! $has($id), ARRAY_FILTER_USE_KEY)),
            'matchedOptional' => array_values(array_filter($job->optionalSkills, $has, ARRAY_FILTER_USE_KEY)),
            'missingOptional' => array_values(array_filter($job->optionalSkills, fn ($id) => ! $has($id), ARRAY_FILTER_USE_KEY)),
        ];

        $wReq = config('matching.skill_weight.required');
        $wOpt = config('matching.skill_weight.optional');
        $total = count($job->requiredSkills) * $wReq + count($job->optionalSkills) * $wOpt;

        if ($total === 0) {
            return [$this->notApplicable('Tin không liệt kê kỹ năng.'), $evidence];
        }

        $got = count($evidence['matchedRequired']) * $wReq + count($evidence['matchedOptional']) * $wOpt;
        $matched = count($evidence['matchedRequired']) + count($evidence['matchedOptional']);
        $all = count($job->requiredSkills) + count($job->optionalSkills);

        return [$this->applicable($got / $total, "Có {$matched}/{$all} kỹ năng tin yêu cầu."), $evidence];
    }

    private function keywords(StudentFeatures $student, JobRequirements $job): array
    {
        if ($job->keywords === []) {
            return [$this->notApplicable('Tin không có mô tả để so sánh.'), []];
        }

        if ($student->keywords === []) {
            return [$this->applicable(0, 'Hồ sơ chưa có giới thiệu hoặc CV đọc được.'), []];
        }

        $matched = array_values(array_intersect($job->keywords, $student->keywords));
        $coverage = count($matched) / count($job->keywords);
        $ratio = min(1, $coverage / config('matching.keyword_full_coverage'));

        return [$this->applicable($ratio, 'Trùng '.count($matched).'/'.count($job->keywords).' từ khóa trong mô tả tin.'), $matched];
    }

    private function field(StudentFeatures $student, JobRequirements $job): array
    {
        if ($job->fields === []) {
            return $this->notApplicable('Tin không gắn với ngành cụ thể.');
        }

        if ($student->fields === []) {
            return $this->notApplicable('Chưa xác định được ngành học từ hồ sơ.');
        }

        $labels = fn (array $codes) => implode(', ', array_map(fn ($c) => config("matching.fields.$c.label"), $codes));

        return array_intersect($job->fields, $student->fields) !== []
            ? $this->applicable(1, 'Ngành học đúng lĩnh vực '.$labels($job->fields).'.')
            : $this->applicable(0, 'Tin thuộc lĩnh vực '.$labels($job->fields).', khác ngành học của bạn.');
    }

    private function location(StudentFeatures $student, JobRequirements $job): array
    {
        if ($job->isRemote) {
            return $this->applicable(1, 'Làm từ xa, không phụ thuộc nơi ở.');
        }

        if ($job->city === null || $student->city === null) {
            return $this->notApplicable('Thiếu thông tin khu vực để so sánh.');
        }

        return $job->city === $student->city
            ? $this->applicable(1, 'Cùng thành phố với nơi bạn ở.')
            : $this->applicable(0, 'Khác thành phố với nơi bạn ở.');
    }

    /** @param list<array{weight: int, score: int, applicable: bool}> $breakdown */
    private function total(array $breakdown): int
    {
        $weightSum = 0;
        $weighted = 0;

        foreach ($breakdown as $item) {
            if ($item['applicable']) {
                $weightSum += $item['weight'];
                $weighted += $item['weight'] * $item['score'];
            }
        }

        return $weightSum === 0 ? 0 : (int) round($weighted / $weightSum);
    }

    private function applicable(float $ratio, string $detail): array
    {
        return ['score' => (int) round($ratio * 100), 'applicable' => true, 'detail' => $detail];
    }

    private function notApplicable(string $detail): array
    {
        return ['score' => 0, 'applicable' => false, 'detail' => $detail];
    }
}
