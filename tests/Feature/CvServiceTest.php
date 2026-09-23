<?php

namespace Tests\Feature;

use App\Models\Skill;
use App\Models\Student;
use App\Services\Cv\CvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CvFiles;
use Tests\TestCase;

class CvServiceTest extends TestCase
{
    use RefreshDatabase;

    private function upload(Student $student, string $path, string $name = 'cv.docx'): void
    {
        app(CvService::class)->upload($student, new UploadedFile($path, $name, null, null, true));
    }

    public function test_upload_stores_privately_and_extracts_skills(): void
    {
        Storage::fake('local');
        $laravel = Skill::create(['name' => 'Laravel', 'aliases' => ['laravel']]);
        Skill::create(['name' => 'Python', 'aliases' => []]);
        $student = Student::factory()->create();

        $this->upload($student, CvFiles::docx("Đại học Bách Khoa\nKỹ năng: Laravel, REST API\nEmail: sv@gmail.com"));

        $cv = $student->fresh()->cv;
        $this->assertSame('parsed', $cv->parse_status);
        $this->assertSame('sv@gmail.com', $cv->parsed['email']);
        Storage::disk('local')->assertExists($cv->path);
        $this->assertStringStartsWith("cvs/{$student->id}/", $cv->path);
        $this->assertSame(['Laravel'], $student->skills()->pluck('name')->all());
        $this->assertSame('cv', $student->skills()->first()->pivot->source);
        $this->assertSame($laravel->id, $student->skills()->first()->id);
    }

    public function test_reupload_replaces_file_and_keeps_manual_skills(): void
    {
        Storage::fake('local');
        $php = Skill::create(['name' => 'PHP', 'aliases' => []]);
        $figma = Skill::create(['name' => 'Figma', 'aliases' => []]);
        Skill::create(['name' => 'Laravel', 'aliases' => []]);
        $student = Student::factory()->create();
        $student->skills()->attach($figma->id, ['source' => 'manual']);

        $this->upload($student, CvFiles::docx('Kinh nghiệm dùng Laravel và PHP trong đồ án'));
        $firstPath = $student->fresh()->cv->path;

        $this->upload($student, CvFiles::docx('Kinh nghiệm chỉ dùng PHP trong đồ án môn học'));

        Storage::disk('local')->assertMissing($firstPath);
        $this->assertEqualsCanonicalizing(['Figma', 'PHP'], $student->skills()->pluck('name')->all());
        $this->assertSame(1, $student->fresh()->cv()->count());
        $this->assertSame($php->id, $student->skills()->wherePivot('source', 'cv')->first()->id);
    }

    public function test_broken_file_is_saved_with_failed_status(): void
    {
        Storage::fake('local');
        $student = Student::factory()->create();
        $path = tempnam(sys_get_temp_dir(), 'cv');
        file_put_contents($path, '%PDF-1.4 hỏng');

        $this->upload($student, $path, 'cv.pdf');

        $cv = $student->fresh()->cv;
        $this->assertContains($cv->parse_status, ['failed', 'empty']);
        $this->assertNotNull($cv->parse_error);
    }

    public function test_profile_score_reflects_parsed_cv(): void
    {
        Storage::fake('local');
        // Factory điền đủ trường, bio, sđt... = 60 điểm; chưa có kỹ năng (20) và CV (20).
        $student = Student::factory()->create();

        $this->upload($student, CvFiles::pdf("Skills: Git, Docker\nExperience: intern at a startup"), 'cv.pdf');

        $this->assertSame(80, $student->fresh()->profile_score);
    }

    public function test_delete_removes_file_and_cv_skills(): void
    {
        Storage::fake('local');
        Skill::create(['name' => 'Laravel', 'aliases' => []]);
        $student = Student::factory()->create();
        $this->upload($student, CvFiles::docx('Làm dự án Laravel cho câu lạc bộ trường'));
        $path = $student->fresh()->cv->path;

        app(CvService::class)->delete($student->fresh());

        Storage::disk('local')->assertMissing($path);
        $this->assertNull($student->fresh()->cv);
        $this->assertSame(0, $student->skills()->count());
    }
}
