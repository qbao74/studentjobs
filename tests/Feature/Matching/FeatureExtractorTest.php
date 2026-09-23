<?php

namespace Tests\Feature\Matching;

use App\Models\Cv;
use App\Models\JobPost;
use App\Models\Skill;
use App\Models\Student;
use App\Services\Matching\FeatureExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureExtractorTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_requirements_split_required_and_optional_skills(): void
    {
        $php = Skill::create(['name' => 'PHP']);
        $git = Skill::create(['name' => 'Git']);
        $job = JobPost::factory()->create([
            'title' => 'Backend Developer',
            'description' => 'Xây API cho ứng dụng đặt lịch.',
            'requirements' => ['Biết Laravel', 'Hiểu REST'],
            'location' => 'Thủ Đức, TP.HCM',
            'is_remote' => false,
        ]);
        $job->skills()->attach([$php->id => ['is_required' => true], $git->id => ['is_required' => false]]);

        $req = app(FeatureExtractor::class)->forJob($job);

        $this->assertSame([$php->id => 'PHP'], $req->requiredSkills);
        $this->assertSame([$git->id => 'Git'], $req->optionalSkills);
        $this->assertContains('laravel', $req->keywords);
        $this->assertSame(['it'], $req->fields);
        $this->assertSame('hcm', $req->city);
    }

    public function test_student_features_merge_manual_skills_bio_and_cv(): void
    {
        $student = Student::factory()->create([
            'major' => 'Khoa học máy tính',
            'bio' => 'Thích làm backend với Laravel',
            'location' => 'Sài Gòn',
        ]);
        $student->skills()->attach(Skill::create(['name' => 'PHP'])->id, ['source' => 'manual']);
        Cv::create([
            'student_id' => $student->id, 'original_name' => 'cv.pdf', 'path' => 'x',
            'parse_status' => 'parsed', 'parsed' => ['keywords' => ['docker', 'api']],
        ]);

        $f = app(FeatureExtractor::class)->forStudent($student);

        $this->assertSame(['PHP'], array_values($f->skills));
        $this->assertContains('laravel', $f->keywords);
        $this->assertContains('docker', $f->keywords);
        $this->assertSame(['it'], $f->fields);
        $this->assertSame('hcm', $f->city);
        $this->assertTrue($f->hasCv);
    }

    public function test_vietnamese_word_it_is_not_read_as_it_field(): void
    {
        $this->assertSame([], app(FeatureExtractor::class)->detectFields('Team ít formal, vui vẻ'));
    }
}
