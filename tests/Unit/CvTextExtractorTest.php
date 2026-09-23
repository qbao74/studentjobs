<?php

namespace Tests\Unit;

use App\Services\Cv\CvTextExtractor;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\CvFiles;

class CvTextExtractorTest extends TestCase
{
    public function test_reads_text_from_docx(): void
    {
        $text = (new CvTextExtractor)->extract(CvFiles::docx("Kỹ năng\nLaravel & ReactJS"), CvTextExtractor::DOCX);

        $this->assertStringContainsString('Kỹ năng', $text);
        $this->assertStringContainsString('Laravel & ReactJS', $text);
    }

    public function test_reads_text_from_pdf(): void
    {
        $text = (new CvTextExtractor)->extract(CvFiles::pdf("Skills\nPHP, MySQL, Git"), CvTextExtractor::PDF);

        $this->assertStringContainsString('PHP, MySQL, Git', $text);
    }

    public function test_broken_file_throws_readable_error(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'cv');
        file_put_contents($path, 'không phải pdf');

        $this->expectException(RuntimeException::class);
        (new CvTextExtractor)->extract($path, CvTextExtractor::PDF);
    }

    public function test_unsupported_type_is_rejected(): void
    {
        $this->expectException(RuntimeException::class);
        (new CvTextExtractor)->extract('/tmp/x', 'image/png');
    }
}
