<?php

namespace App\Services\Cv;

use App\Support\TextNormalizer;

/**
 * Chữ thô của CV → dữ liệu có cấu trúc (lưu vào cvs.parsed).
 * Chỉ ghi lại những gì thật sự tìm thấy; không có thì để null/rỗng, không bịa.
 */
class CvParser
{
    public function __construct(private SkillExtractor $skills) {}

    /**
     * @param  iterable<array{id: int, name: string, aliases?: list<string>|null}>  $skillCatalog
     * @return array{skills: list<array{id: int, name: string, matched: string}>, email: ?string, phone: ?string, education: ?string, experience_mentions: int, keywords: list<string>, word_count: int}
     */
    public function parse(string $rawText, iterable $skillCatalog): array
    {
        $keywords = TextNormalizer::keywords($rawText);

        return [
            'skills' => $this->skills->extract($rawText, $skillCatalog),
            'email' => $this->firstMatch('/[\w.+-]+@[\w-]+\.[\w.-]+/u', $rawText),
            'phone' => $this->phone($rawText),
            'education' => $this->educationLine($rawText),
            'experience_mentions' => preg_match_all('/\b(kinh nghiem|experience|thuc tap|intern|du an|project)\b/', TextNormalizer::normalize($rawText)),
            'keywords' => array_slice($keywords, 0, 200),
            'word_count' => str_word_count(TextNormalizer::normalize($rawText)),
        ];
    }

    private function firstMatch(string $pattern, string $text): ?string
    {
        return preg_match($pattern, $text, $m) ? $m[0] : null;
    }

    /** Số điện thoại Việt Nam: 0xxx hoặc +84xxx, cho phép dấu cách/chấm/gạch. */
    private function phone(string $text): ?string
    {
        if (! preg_match('/(?:\+84|0)(?:[\s.-]?\d){9}/', $text, $m)) {
            return null;
        }

        return preg_replace('/[\s.-]/', '', $m[0]);
    }

    /** Dòng đầu tiên nhắc tới trường học. */
    private function educationLine(string $text): ?string
    {
        foreach (preg_split('/\R/u', $text) as $line) {
            $normalized = TextNormalizer::normalize($line);

            if (preg_match('/\b(dai hoc|cao dang|hoc vien|university|college)\b/', $normalized)) {
                return mb_substr(trim($line), 0, 150);
            }
        }

        return null;
    }
}
