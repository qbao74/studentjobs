<?php

namespace App\Services\Cv;

use App\Models\Cv;
use App\Models\Skill;
use App\Models\Student;
use App\Services\Profile\ProfileScoreCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Điều phối xử lý CV: lưu file → trích chữ → phân tích → cập nhật kỹ năng → chấm lại độ đầy đủ hồ sơ.
 */
class CvService
{
    public const DISK = 'local';

    public function __construct(
        private CvTextExtractor $extractor,
        private CvParser $parser,
        private ProfileScoreCalculator $profileScore,
    ) {}

    public function upload(Student $student, UploadedFile $file): Cv
    {
        $mime = $this->detectType($file);
        $extension = $mime === CvTextExtractor::PDF ? 'pdf' : 'docx';

        // Tên file do server sinh ngẫu nhiên, không dùng tên người dùng gửi lên (tránh ../ và ghi đè).
        $path = $file->storeAs("cvs/{$student->id}", Str::random(40).'.'.$extension, self::DISK);

        if ($path === false) {
            throw new RuntimeException('Không lưu được file CV.');
        }

        // Truy vấn lại thay vì $student->cv: quan hệ đã nạp trước đó có thể cũ.
        $oldPath = $student->cv()->value('path');

        $analysis = $this->analyze(Storage::disk(self::DISK)->path($path), $mime);

        $cv = DB::transaction(function () use ($student, $file, $path, $mime, $analysis) {
            $cv = Cv::updateOrCreate(['student_id' => $student->id], [
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'path' => $path,
                'mime_type' => $mime,
                'size' => $file->getSize(),
            ] + $analysis);

            $this->syncCvSkills($student, $analysis['parsed']['skills'] ?? []);

            return $cv;
        });

        if ($oldPath && $oldPath !== $path) {
            Storage::disk(self::DISK)->delete($oldPath);
        }

        $this->profileScore->refresh($student->fresh());

        return $cv;
    }

    public function delete(Student $student): void
    {
        $cv = $student->cv()->first();

        if (! $cv) {
            return;
        }

        DB::transaction(function () use ($student, $cv) {
            $cv->delete();
            $this->syncCvSkills($student, []);
        });

        Storage::disk(self::DISK)->delete($cv->path);
        $this->profileScore->refresh($student->fresh());
    }

    /**
     * Một số file DOCX thật bị libmagic nhận là application/zip hoặc octet-stream.
     * Chấp nhận là DOCX khi đuôi .docx và bên trong có word/document.xml.
     */
    private function detectType(UploadedFile $file): string
    {
        $mime = (string) $file->getMimeType();

        if ($mime === CvTextExtractor::PDF || $mime === CvTextExtractor::DOCX) {
            return $mime;
        }

        if (strtolower($file->getClientOriginalExtension()) === 'docx' && in_array($mime, ['application/zip', 'application/octet-stream'], true)) {
            $zip = new ZipArchive;

            if ($zip->open($file->getRealPath()) === true) {
                $isWord = $zip->locateName('word/document.xml') !== false;
                $zip->close();

                if ($isWord) {
                    return CvTextExtractor::DOCX;
                }
            }
        }

        return $mime;
    }

    /**
     * @return array{extracted_text: ?string, parsed: ?array, parse_status: string, parse_error: ?string}
     */
    private function analyze(string $absolutePath, string $mime): array
    {
        try {
            $text = $this->extractor->extract($absolutePath, $mime);
        } catch (RuntimeException $e) {
            return ['extracted_text' => null, 'parsed' => null, 'parse_status' => 'failed', 'parse_error' => $e->getMessage()];
        }

        if (mb_strlen($text) < 20) {
            return [
                'extracted_text' => $text ?: null,
                'parsed' => null,
                'parse_status' => 'empty',
                'parse_error' => 'CV không có chữ đọc được (có thể là ảnh scan). Hệ thống sẽ dùng kỹ năng bạn tự chọn.',
            ];
        }

        $catalog = Skill::query()->get(['id', 'name', 'aliases'])
            ->map(fn (Skill $s) => ['id' => $s->id, 'name' => $s->name, 'aliases' => $s->aliases ?? []]);

        return [
            'extracted_text' => mb_substr($text, 0, 60000),
            'parsed' => $this->parser->parse($text, $catalog),
            'parse_status' => 'parsed',
            'parse_error' => null,
        ];
    }

    /**
     * Kỹ năng đọc từ CV có source = cv. Kỹ năng sinh viên tự chọn (manual) không bao giờ bị xóa ở đây.
     *
     * @param  list<array{id: int}>  $found
     */
    private function syncCvSkills(Student $student, array $found): void
    {
        $foundIds = array_column($found, 'id');

        DB::table('student_skill')
            ->where('student_id', $student->id)
            ->where('source', 'cv')
            ->whereNotIn('skill_id', $foundIds)
            ->delete();

        $existing = DB::table('student_skill')->where('student_id', $student->id)->pluck('skill_id')->all();
        $new = array_diff($foundIds, $existing);

        $student->skills()->attach(array_fill_keys($new, ['source' => 'cv']));
    }
}
