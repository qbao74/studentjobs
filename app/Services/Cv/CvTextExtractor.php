<?php

namespace App\Services\Cv;

use RuntimeException;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;
use ZipArchive;

/**
 * Bước DATA EXTRACTION: file thô (PDF/DOCX) → chuỗi chữ thô.
 * Không hiểu nội dung, chỉ lấy chữ ra.
 */
class CvTextExtractor
{
    public const PDF = 'application/pdf';

    public const DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    /**
     * @throws RuntimeException khi file hỏng hoặc không đúng định dạng
     */
    public function extract(string $absolutePath, string $mimeType): string
    {
        $text = match ($mimeType) {
            self::PDF => $this->fromPdf($absolutePath),
            self::DOCX => $this->fromDocx($absolutePath),
            default => throw new RuntimeException('Định dạng CV không được hỗ trợ.'),
        };

        return trim($text);
    }

    private function fromPdf(string $path): string
    {
        try {
            return (new PdfParser)->parseFile($path)->getText();
        } catch (Throwable $e) {
            throw new RuntimeException('Không đọc được file PDF (file hỏng hoặc có mật khẩu).', previous: $e);
        }
    }

    /** DOCX là file zip; nội dung chính nằm trong word/document.xml. */
    private function fromDocx(string $path): string
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('Không mở được file DOCX.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('File DOCX không có nội dung văn bản.');
        }

        // Mỗi đoạn </w:p> và ngắt dòng <w:br/> thành xuống dòng, tab <w:tab/> thành khoảng trắng.
        $xml = str_replace(['</w:p>', '<w:br/>', '<w:tab/>'], ["\n", "\n", ' '], $xml);

        return html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
