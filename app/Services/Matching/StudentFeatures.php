<?php

namespace App\Services\Matching;

/** Kết quả bước FEATURE EXTRACTION phía sinh viên. */
final readonly class StudentFeatures
{
    /**
     * @param  array<int, string>  $skills  id => tên (tự chọn + đọc từ CV)
     * @param  list<string>  $keywords  từ giới thiệu + CV, đã chuẩn hóa
     * @param  list<string>  $fields  lĩnh vực suy ra từ ngành học
     */
    public function __construct(
        public int $studentId,
        public array $skills,
        public array $keywords,
        public array $fields,
        public ?string $city,
        public bool $hasCv,
    ) {}
}
