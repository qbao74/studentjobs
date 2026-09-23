<?php

namespace App\Services\Cv;

use App\Support\TextNormalizer;

/**
 * Bước FEATURE EXTRACTION cho kỹ năng: tìm tên kỹ năng hoặc tên gọi khác (aliases) trong văn bản.
 * Dùng danh mục kỹ năng có sẵn trong database, không đoán kỹ năng mới.
 */
class SkillExtractor
{
    /**
     * @param  iterable<array{id: int, name: string, aliases?: list<string>|null}>  $catalog
     * @return list<array{id: int, name: string, matched: string}>
     */
    public function extract(string $rawText, iterable $catalog): array
    {
        $normalized = TextNormalizer::normalize($rawText);
        $found = [];

        foreach ($catalog as $skill) {
            $terms = array_unique(array_merge([$skill['name']], $skill['aliases'] ?? []));

            foreach ($terms as $term) {
                if ($this->matches($rawText, $normalized, $term)) {
                    $found[] = ['id' => $skill['id'], 'name' => $skill['name'], 'matched' => $term];
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Từ viết tắt ngắn (ai, ps, ig, ui) dễ trùng chữ thường ("ai" = "ai đó"),
     * nên chỉ tính khi viết HOA trong văn bản gốc.
     */
    private function matches(string $rawText, string $normalized, string $term): bool
    {
        if (mb_strlen($term) <= 2) {
            return (bool) preg_match('/(?<![\p{L}\p{N}])'.preg_quote(mb_strtoupper($term), '/').'(?![\p{L}\p{N}])/u', $rawText);
        }

        return TextNormalizer::containsTerm($normalized, $term);
    }
}
