<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Bước NORMALIZATION: đưa mọi văn bản (CV, tin tuyển) về cùng một dạng để so sánh được:
 * chữ thường, bỏ dấu tiếng Việt, bỏ ký tự lạ, gộp khoảng trắng.
 * Giữ lại + # . / vì có trong tên kỹ năng: c++, c#, node.js, ui/ux.
 */
class TextNormalizer
{
    /** Từ quá phổ biến, không mang ý nghĩa khi so khớp. Đã bỏ dấu. */
    private const STOPWORDS = [
        'va', 'cua', 'cho', 'voi', 'cac', 'nhung', 'mot', 'la', 'co', 'khong', 'duoc', 'trong', 'tren', 'den', 'tu',
        'de', 'nguoi', 'lam', 'viec', 'ban', 'minh', 'se', 'da', 'dang', 'theo', 'nhu', 'khi', 'hoac', 'neu', 'thi',
        'nay', 'do', 've', 'ra', 'vao', 'rat', 'hon', 'nhat', 'cung', 'tot', 'biet', 'can', 'muon', 'the', 'and',
        'or', 'of', 'to', 'in', 'for', 'with', 'on', 'at', 'is', 'are', 'be', 'an', 'a', 'my', 'i', 'as', 'by',
        'tuan', 'ngay', 'gio', 'thang', 'nam', 'buoi',
    ];

    public static function normalize(string $text): string
    {
        $text = Str::lower(Str::ascii($text));
        $text = preg_replace('/[^a-z0-9+#.\/]+/', ' ', $text);
        // Dấu chấm cuối câu ("php.") khác dấu chấm trong tên ("node.js").
        $text = preg_replace('/\.(?![a-z0-9])|(?<![a-z0-9])\./', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /** Có chứa cụm từ đứng riêng không (không tính "java" nằm trong "javascript"). */
    public static function containsTerm(string $normalizedText, string $term): bool
    {
        $term = self::normalize($term);

        if ($term === '') {
            return false;
        }

        return (bool) preg_match('/(?<![a-z0-9+#])'.preg_quote($term, '/').'(?![a-z0-9+#])/', $normalizedText);
    }

    /**
     * Các từ có nghĩa (đã chuẩn hóa, bỏ stopword, bỏ số thuần), không trùng.
     *
     * @return list<string>
     */
    public static function keywords(string $text): array
    {
        $words = explode(' ', self::normalize($text));

        return array_values(array_unique(array_filter(
            $words,
            fn (string $w) => strlen($w) >= 2 && ! ctype_digit($w) && ! in_array($w, self::STOPWORDS, true),
        )));
    }
}
