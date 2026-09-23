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
    public function explain(MatchResult $result, StudentFeatures $student): array
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
            'comment' => $this->comment($result, $student, $level),
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

    private function comment(MatchResult $result, StudentFeatures $student, string $level): string
    {
        $sentences = ["{$level} ({$result->score}%)."];

        if ($result->missingRequired !== []) {
            $sentences[] = 'Nên học thêm '.implode(', ', array_slice($result->missingRequired, 0, 3)).' để tăng cơ hội.';
        } elseif ($result->missingOptional !== []) {
            $sentences[] = 'Bổ sung '.implode(', ', array_slice($result->missingOptional, 0, 2)).' sẽ giúp hồ sơ nổi bật hơn.';
        } else {
            $sentences[] = 'Bạn đã có đủ kỹ năng tin yêu cầu.';
        }

        if (! $student->hasCv) {
            $sentences[] = 'Tải CV đọc được chữ để hệ thống so khớp kinh nghiệm chính xác hơn.';
        }

        return implode(' ', $sentences);
    }
}
