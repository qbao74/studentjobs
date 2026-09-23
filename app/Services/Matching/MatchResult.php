<?php

namespace App\Services\Matching;

/** Kết quả bước SCORING: tổng điểm + điểm từng tiêu chí + bằng chứng dùng cho giải thích. */
final readonly class MatchResult
{
    /**
     * @param  list<array{key: string, label: string, weight: int, score: int, applicable: bool, detail: string}>  $breakdown
     * @param  list<string>  $matchedRequired
     * @param  list<string>  $missingRequired
     * @param  list<string>  $matchedOptional
     * @param  list<string>  $missingOptional
     * @param  list<string>  $matchedKeywords
     */
    public function __construct(
        public int $score,
        public array $breakdown,
        public array $matchedRequired,
        public array $missingRequired,
        public array $matchedOptional,
        public array $missingOptional,
        public array $matchedKeywords,
    ) {}

    /** @return array{key: string, label: string, weight: int, score: int, applicable: bool, detail: string}|null */
    public function criterion(string $key): ?array
    {
        foreach ($this->breakdown as $item) {
            if ($item['key'] === $key) {
                return $item;
            }
        }

        return null;
    }
}
