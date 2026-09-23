<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Skill;
use App\Models\Student;
use App\Services\Cv\CvService;
use App\Services\Employer\JobPostService;
use App\Services\Matching\FeatureExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CvFiles;
use Tests\TestCase;

class AiReadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cv_keeps_only_skills_whose_evidence_is_in_the_file(): void
    {
        Storage::fake('local');
        $this->useAi();
        $this->fakeAi([
            'skills' => [
                ['name' => 'React', 'importance' => 'demonstrated', 'evidence' => 'xây giao diện web'],
                ['name' => 'Kubernetes', 'importance' => 'listed', 'evidence' => 'điều hành cụm Kubernetes'],
            ],
            'field' => 'it',
            'city' => 'hcm',
            'remote' => false,
            'experience' => [
                ['role' => 'Câu lạc bộ', 'months' => 12, 'evidence' => 'câu lạc bộ suốt một năm'],
            ],
        ]);

        $student = Student::factory()->create([
            'major' => 'Quản trị nhà hàng',
            'location' => 'Biên Hòa',
        ]);

        app(CvService::class)->upload($student, new UploadedFile(
            CvFiles::docx("Mình từng xây giao diện web cho câu lạc bộ suốt một năm.\nSống ở TP.HCM.\nEmail: sv@gmail.com"),
            'cv.docx',
            null,
            null,
            true,
        ));

        $cv = $student->fresh()->cv;
        $this->assertSame('parsed', $cv->parse_status);
        $this->assertSame('ai', $cv->parsed['reader']);
        $this->assertSame('sv@gmail.com', $cv->parsed['email']);
        $this->assertSame(['React'], array_column($cv->parsed['skills'], 'name'));
        $this->assertSame('demonstrated', $cv->parsed['skills'][0]['importance']);
        $this->assertSame('cv', $student->skills()->first()->pivot->source);
        $this->assertNull(Skill::where('name', 'Kubernetes')->first());

        $features = app(FeatureExtractor::class)->forStudent($student->fresh());
        $this->assertSame(['it'], $features->fields);
        $this->assertSame('hcm', $features->city);

        Http::assertSent(function ($request) {
            $content = $request->data()['messages'][1]['content'] ?? '';

            return str_contains($content, 'xây giao diện web');
        });
    }

    public function test_failed_ai_call_does_not_invent_skills(): void
    {
        Storage::fake('local');
        $this->useAi();
        Http::fake([
            'https://ai.test/*' => Http::response(['error' => 'down'], 503),
        ]);
        $student = Student::factory()->create();

        app(CvService::class)->upload($student, new UploadedFile(
            CvFiles::docx('Mình từng xây giao diện web cho câu lạc bộ suốt một năm.'),
            'cv.docx',
            null,
            null,
            true,
        ));

        $cv = $student->fresh()->cv;
        $this->assertSame('failed', $cv->parse_status);
        $this->assertStringContainsString('AI không đọc được', $cv->parse_error);
        $this->assertSame(0, $student->skills()->count());
    }

    public function test_job_reading_adds_skills_without_downgrading_employer_tags(): void
    {
        $this->useAi();
        $this->fakeAi([
            'skills' => [
                ['name' => 'PHP', 'importance' => 'optional', 'evidence' => 'Cần biết PHP'],
                ['name' => 'Canva', 'importance' => 'optional', 'evidence' => 'Có Canva là lợi thế'],
            ],
            'field' => 'marketing',
            'city' => 'hn',
            'remote' => false,
            'experience' => [],
        ]);
        Skill::create(['name' => 'PHP', 'aliases' => []]);
        $company = Employer::factory()->create()->company;

        $job = app(JobPostService::class)->create($company, [
            'fields' => [
                'title' => 'Cộng tác viên viết bài',
                'salary' => '5 triệu',
                'location' => 'Biên Hòa',
                'is_remote' => false,
                'type' => 'Part-time',
                'hours' => '3 giờ/ngày',
                'description' => 'Cần biết PHP để sửa web. Có Canva là lợi thế khi làm ảnh đăng bài. Văn phòng ở Hà Nội.',
                'requirements' => [],
                'benefits' => [],
            ],
            'skills' => ['PHP' => true],
        ]);

        $skills = $job->fresh()->skills->mapWithKeys(fn ($skill) => [$skill->name => (bool) $skill->pivot->is_required])->all();
        $this->assertSame(['PHP' => true, 'Canva' => false], $skills);
        $this->assertSame('marketing', $job->fresh()->analyzed['field']);

        $requirements = app(FeatureExtractor::class)->forJob($job->fresh());
        $this->assertSame(['marketing'], $requirements->fields);
        $this->assertSame('hn', $requirements->city);
        $this->assertSame(['Canva'], array_values($requirements->optionalSkills));
    }

    private function useAi(): void
    {
        config([
            'ai.key' => 'test-key',
            'ai.url' => 'https://ai.test/v1/chat/completions',
            'ai.model' => 'test-model',
            'ai.timeout' => 5,
        ]);
    }

    /** @param  array<string, mixed>  $document */
    private function fakeAi(array $document): void
    {
        Http::fake([
            'https://ai.test/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode($document, JSON_UNESCAPED_UNICODE)]],
                ],
            ]),
        ]);
    }
}
