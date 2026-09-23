<?php

namespace App\Services\Matching;

/**
 * Bước EXPLANATION + RECOMMENDATION: biến con số thành lời.
 * Mọi câu đều dựa trên bằng chứng trong MatchResult, không tự bịa thông tin.
 */
class MatchExplainer
{
    /**
     * @return array{level: string, pros: list<string>, cons: list<string>, comment: string}
     */
    public function explain(MatchResult $result, StudentFeatures $student, JobRequirements $job): array
    {
        $pros = [];
        $cons = [];

        if ($result->matchedRequired !== []) {
            $pros[] = 'Có kỹ năng bắt buộc: '.implode(', ', $result->matchedRequired).'.';
        }
        if ($result->matchedOptional !== []) {
            $pros[] = 'Có thêm điểm cộng: '.implode(', ', $result->matchedOptional).'.';
        }
        if ($result->missingRequired !== []) {
            $cons[] = 'Thiếu kỹ năng bắt buộc: '.implode(', ', $result->missingRequired).'.';
        }
        if ($result->missingOptional !== []) {
            $cons[] = 'Chưa có kỹ năng điểm cộng: '.implode(', ', $result->missingOptional).'.';
        }

        foreach (['keywords', 'field', 'location'] as $key) {
            $criterion = $result->criterion($key);

            if (! $criterion['applicable']) {
                continue;
            }

            if ($criterion['score'] >= 50) {
                $pros[] = $criterion['detail'];
            } else {
                $cons[] = $criterion['detail'];
            }
        }

        $level = $this->level($result->score);

        return [
            'level' => $level,
            'pros' => $pros,
            'cons' => $cons,
            'comment' => $this->comment($result, $student, $job, $level),
        ];
    }

    public function level(int $score): string
    {
        foreach (config('matching.levels') as $min => $label) {
            if ($score >= $min) {
                return $label;
            }
        }

        return 'Chưa phù hợp';
    }

    private function comment(MatchResult $result, StudentFeatures $student, JobRequirements $job, string $level): string
    {
        $sentences = ["{$level} ({$result->score}%)."];

        if ($result->missingRequired !== []) {
            $sentences[] = 'Nên học thêm '.implode(', ', array_slice($result->missingRequired, 0, 3)).' để tăng cơ hội.';
        } elseif ($result->missingOptional !== []) {
            $sentences[] = 'Bổ sung '.implode(', ', array_slice($result->missingOptional, 0, 2)).' sẽ giúp hồ sơ nổi bật hơn.';
        } else {
            $sentences[] = 'Bạn đã có đủ kỹ năng tin yêu cầu.';
        }

        $edit = $this->cvEdit($result, $student, $job);

        if ($edit !== null) {
            $sentences[] = $edit;
        }

        if (! $student->hasCv) {
            $sentences[] = 'Tải CV đọc được chữ để hệ thống so khớp kinh nghiệm chính xác hơn.';
        }

        return implode(' ', $sentences);
    }

    /** Gợi ý sửa CV chỉ từ câu AI đã trích trong tin hoặc trong CV. Không có câu đó thì không khuyên. */
    private function cvEdit(MatchResult $result, StudentFeatures $student, JobRequirements $job): ?string
    {
        $requiredByName = array_flip($job->requiredSkills);

        foreach (array_slice($result->missingRequired, 0, 3) as $name) {
            $id = $requiredByName[$name] ?? null;
            $quote = is_int($id) ? ($job->skillEvidence[$id] ?? null) : null;

            if (is_string($quote) && $quote !== '') {
                return 'Tin viết: «'.$quote.'». Hãy thêm vào CV một dòng dự án hoặc việc làm có nhắc '.$name.'.';
            }
        }

        $wanted = $job->requiredSkills + $job->optionalSkills;

        foreach ($student->skills as $id => $name) {
            if (! isset($wanted[$id]) || ($student->skillImportance[$id] ?? null) !== 'listed') {
                continue;
            }

            $quote = $student->skillEvidence[$id] ?? null;

            if (is_string($quote) && $quote !== '') {
                return 'CV chỉ nêu «'.$quote.'». Hãy viết thêm một câu việc đã làm với '.$name.'.';
            }
        }

        return null;
    }
}
