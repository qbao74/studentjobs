<?php

namespace Tests\Unit;

use App\Services\Cv\CvParser;
use App\Services\Cv\SkillExtractor;
use App\Support\TextNormalizer;
use PHPUnit\Framework\TestCase;

class CvParsingTest extends TestCase
{
    private array $catalog = [
        ['id' => 1, 'name' => 'JavaScript', 'aliases' => ['js', 'es6']],
        ['id' => 2, 'name' => 'Java', 'aliases' => []],
        ['id' => 3, 'name' => 'Node.js', 'aliases' => ['nodejs']],
        ['id' => 4, 'name' => 'Illustrator', 'aliases' => ['ai']],
        ['id' => 5, 'name' => 'SQL', 'aliases' => ['mysql', 'postgresql']],
        ['id' => 6, 'name' => 'C++', 'aliases' => []],
        ['id' => 7, 'name' => 'UI/UX', 'aliases' => ['ui', 'ux']],
    ];

    public function test_normalize_removes_vietnamese_accents_and_noise(): void
    {
        $this->assertSame('ky nang lap trinh php node.js c++', TextNormalizer::normalize('Kỹ năng: Lập trình PHP, Node.js & C++.'));
    }

    public function test_contains_term_respects_word_boundaries(): void
    {
        $text = TextNormalizer::normalize('Tôi dùng JavaScript hằng ngày');

        $this->assertTrue(TextNormalizer::containsTerm($text, 'javascript'));
        $this->assertFalse(TextNormalizer::containsTerm($text, 'java'));
    }

    public function test_keywords_drop_stopwords(): void
    {
        $this->assertSame(['thiet', 'ke', 'giao', 'dien', 'figma'], TextNormalizer::keywords('Thiết kế giao diện và Figma cho 3 tuần'));
    }

    public function test_extracts_skills_by_name_and_alias(): void
    {
        $found = (new SkillExtractor)->extract('Dự án: ES6, NodeJS, MySQL, C++', $this->catalog);

        $this->assertEqualsCanonicalizing(['JavaScript', 'Node.js', 'SQL', 'C++'], array_column($found, 'name'));
        $this->assertNotContains('Java', array_column($found, 'name'));
    }

    public function test_short_aliases_only_match_when_uppercase(): void
    {
        $extractor = new SkillExtractor;

        $this->assertSame([], $extractor->extract('Không biết ai sẽ giúp, ui chà', $this->catalog));
        $this->assertEqualsCanonicalizing(
            ['Illustrator', 'UI/UX'],
            array_column($extractor->extract('Thành thạo AI và thiết kế UI', $this->catalog), 'name'),
        );
    }

    public function test_parser_collects_contact_education_and_skills(): void
    {
        $text = "Nguyễn Văn A\nEmail: a.nguyen@gmail.com | SĐT: 0901 234 567\nĐại học Bách Khoa TP.HCM - CNTT\nKinh nghiệm: thực tập Node.js";

        $parsed = (new CvParser(new SkillExtractor))->parse($text, $this->catalog);

        $this->assertSame('a.nguyen@gmail.com', $parsed['email']);
        $this->assertSame('0901234567', $parsed['phone']);
        $this->assertSame('Đại học Bách Khoa TP.HCM - CNTT', $parsed['education']);
        $this->assertSame(['Node.js'], array_column($parsed['skills'], 'name'));
        $this->assertGreaterThanOrEqual(2, $parsed['experience_mentions']);
    }

    public function test_parser_returns_nulls_when_nothing_found(): void
    {
        $parsed = (new CvParser(new SkillExtractor))->parse('', $this->catalog);

        $this->assertNull($parsed['email']);
        $this->assertNull($parsed['education']);
        $this->assertSame([], $parsed['skills']);
        $this->assertSame(0, $parsed['word_count']);
    }
}
