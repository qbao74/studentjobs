<?php

namespace App\Services\Matching;

/** Kết quả bước REQUIREMENT ANALYSIS: tin tuyển cần gì, ở dạng máy so sánh được. */
final readonly class JobRequirements
{
    /**
     * @param  array<int, string>  $requiredSkills  id => tên
     * @param  array<int, string>  $optionalSkills  id => tên
     * @param  list<string>  $keywords  đã chuẩn hóa
     * @param  list<string>  $fields  mã lĩnh vực (it, design...)
     * @param  array<int, string>  $skillEvidence  id kỹ năng => câu gốc trong tin
     */
    public function __construct(
        public int $jobId,
        public array $requiredSkills,
        public array $optionalSkills,
        public array $keywords,
        public array $fields,
        public ?string $city,
        public bool $isRemote,
        public array $skillEvidence = [],
    ) {}
}
