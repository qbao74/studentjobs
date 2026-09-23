<?php

namespace Tests\Feature\Matching;

use App\Enums\JobStatus;
use App\Models\JobPost;
use App\Models\JobRecommendation;
use App\Models\Skill;
use App\Models\Student;
use App\Services\Cv\CvService;
use App\Services\Matching\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CvFiles;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function jobNeeding(array $skills, array $attrs = []): JobPost
    {
        $job = JobPost::factory()->create($attrs + ['title' => 'Backend Developer', 'location' => 'Quận 1, TP.HCM']);
        foreach ($skills as $skill) {
            $job->skills()->attach($skill->id, ['is_required' => true]);
        }

        return $job;
    }

    public function test_refresh_for_student_stores_score_breakdown_and_reasons(): void
    {
        $php = Skill::create(['name' => 'PHP']);
        $sql = Skill::create(['name' => 'SQL']);
        $job = $this->jobNeeding([$php, $sql]);
        $student = Student::factory()->create();
        $student->skills()->attach($php->id, ['source' => 'manual']);

        app(RecommendationService::class)->refreshForStudent($student);

        $rec = JobRecommendation::where(['student_id' => $student->id, 'job_post_id' => $job->id])->firstOrFail();
        $this->assertSame(50, collect($rec->breakdown)->firstWhere('key', 'skills')['score']);
        $this->assertContains('Thiếu kỹ năng bắt buộc: SQL.', $rec->cons);
        $this->assertContains('Có kỹ năng bắt buộc: PHP.', $rec->pros);
        $this->assertNotEmpty($rec->comment);
    }

    public function test_closed_or_hidden_jobs_are_not_recommended(): void
    {
        $student = Student::factory()->create();
        $open = JobPost::factory()->create();
        $closed = JobPost::factory()->closed()->create();

        app(RecommendationService::class)->refreshForStudent($student);

        $this->assertSame([$open->id], JobRecommendation::pluck('job_post_id')->all());

        $open->update(['status' => JobStatus::Hidden]);
        app(RecommendationService::class)->refreshForJob($open);

        $this->assertSame(0, JobRecommendation::count());
        $this->assertNotNull($closed);
    }

    public function test_refresh_for_job_scores_every_student(): void
    {
        Student::factory()->count(3)->create();
        $job = JobPost::factory()->create();

        app(RecommendationService::class)->refreshForJob($job);

        $this->assertSame(3, JobRecommendation::where('job_post_id', $job->id)->count());
    }

    public function test_uploading_cv_raises_score_for_matching_job(): void
    {
        Storage::fake('local');
        $laravel = Skill::create(['name' => 'Laravel']);
        $job = $this->jobNeeding([$laravel]);
        $student = Student::factory()->create();

        app(RecommendationService::class)->refreshForStudent($student);
        $before = JobRecommendation::where('job_post_id', $job->id)->value('score');

        app(CvService::class)->upload($student, new UploadedFile(
            CvFiles::docx('Kinh nghiệm backend: Laravel, REST API cho đồ án'), 'cv.docx', null, null, true,
        ));

        $after = JobRecommendation::where('job_post_id', $job->id)->value('score');
        $this->assertGreaterThan($before, $after);
    }

    public function test_refresh_is_idempotent(): void
    {
        Student::factory()->create();
        JobPost::factory()->count(2)->create();
        $service = app(RecommendationService::class);

        $service->refreshAll();
        $service->refreshAll();

        $this->assertSame(2, JobRecommendation::count());
    }
}
