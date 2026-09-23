<?php

namespace App\Services\Matching;

use App\Enums\JobStatus;
use App\Models\JobPost;
use App\Models\JobRecommendation;
use App\Models\Student;

/**
 * Điều phối toàn bộ pipeline và lưu kết quả vào job_recommendations.
 * Tính lại khi: sinh viên đổi hồ sơ/kỹ năng/CV, nhà tuyển dụng tạo/sửa tin.
 */
class RecommendationService
{
    public function __construct(
        private FeatureExtractor $features,
        private MatchScorer $scorer,
        private MatchExplainer $explainer,
    ) {}

    /**
     * Chấm một cặp sinh viên – tin, không lưu.
     *
     * @return array{score: int, level: string, breakdown: array, pros: list<string>, cons: list<string>, comment: string}
     */
    public function evaluate(Student $student, JobPost $job): array
    {
        return $this->run($this->features->forStudent($student), $this->features->forJob($job));
    }

    public function refreshForStudent(Student $student): void
    {
        $studentFeatures = $this->features->forStudent($student);
        $jobs = JobPost::open()->with('skills')->get();

        $rows = $jobs->map(fn (JobPost $job) => $this->row(
            $student->id,
            $job->id,
            $this->run($studentFeatures, $this->features->forJob($job)),
        ));

        $this->save($rows->all());

        JobRecommendation::where('student_id', $student->id)
            ->whereNotIn('job_post_id', $jobs->modelKeys())
            ->delete();
    }

    public function refreshForJob(JobPost $job): void
    {
        if ($job->status !== JobStatus::Open) {
            JobRecommendation::where('job_post_id', $job->id)->delete();

            return;
        }

        $requirements = $this->features->forJob($job->load('skills'));

        Student::with(['skills', 'cv'])->chunkById(200, function ($students) use ($job, $requirements) {
            $this->save($students->map(fn (Student $student) => $this->row(
                $student->id,
                $job->id,
                $this->run($this->features->forStudent($student), $requirements),
            ))->all());
        });
    }

    public function refreshAll(): int
    {
        $count = 0;

        Student::with(['skills', 'cv'])->chunkById(200, function ($students) use (&$count) {
            foreach ($students as $student) {
                $this->refreshForStudent($student);
                $count++;
            }
        });

        return $count;
    }

    private function run(StudentFeatures $student, JobRequirements $job): array
    {
        $result = $this->scorer->score($student, $job);
        $explanation = $this->explainer->explain($result, $student);

        return [
            'score' => $result->score,
            'level' => $explanation['level'],
            'breakdown' => $result->breakdown,
            'pros' => $explanation['pros'],
            'cons' => $explanation['cons'],
            'comment' => $explanation['comment'],
        ];
    }

    private function row(int $studentId, int $jobId, array $evaluation): array
    {
        return [
            'student_id' => $studentId,
            'job_post_id' => $jobId,
            'score' => $evaluation['score'],
            'breakdown' => json_encode($evaluation['breakdown'], JSON_UNESCAPED_UNICODE),
            'pros' => json_encode($evaluation['pros'], JSON_UNESCAPED_UNICODE),
            'cons' => json_encode($evaluation['cons'], JSON_UNESCAPED_UNICODE),
            'comment' => $evaluation['comment'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function save(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        JobRecommendation::upsert(
            $rows,
            uniqueBy: ['student_id', 'job_post_id'],
            update: ['score', 'breakdown', 'pros', 'cons', 'comment', 'updated_at'],
        );
    }
}
