<?php

namespace App\Services\Matching;

use App\Models\JobPost;
use App\Models\Student;
use App\Support\TextNormalizer;

/**
 * Biến dữ liệu thô (model) thành đặc trưng để so sánh:
 * - forJob(): REQUIREMENT ANALYSIS cho tin tuyển
 * - forStudent(): FEATURE EXTRACTION cho hồ sơ sinh viên
 */
class FeatureExtractor
{
    public function forJob(JobPost $job): JobRequirements
    {
        $job->loadMissing('skills');

        $required = [];
        $optional = [];
        foreach ($job->skills as $skill) {
            if ($skill->pivot->is_required) {
                $required[$skill->id] = $skill->name;
            } else {
                $optional[$skill->id] = $skill->name;
            }
        }

        $text = implode("\n", array_filter([
            $job->title,
            $job->description,
            implode("\n", $job->requirements ?? []),
        ]));

        return new JobRequirements(
            jobId: $job->id,
            requiredSkills: $required,
            optionalSkills: $optional,
            keywords: TextNormalizer::keywords($text),
            fields: $this->detectFields($job->title.' '.$job->description),
            city: $this->detectCity((string) $job->location),
            isRemote: (bool) $job->is_remote,
        );
    }

    public function forStudent(Student $student): StudentFeatures
    {
        $student->loadMissing(['skills', 'cv']);

        $cvKeywords = $student->cv?->parse_status === 'parsed'
            ? ($student->cv->parsed['keywords'] ?? [])
            : [];

        return new StudentFeatures(
            studentId: $student->id,
            skills: $student->skills->pluck('name', 'id')->all(),
            keywords: array_values(array_unique(array_merge(
                TextNormalizer::keywords((string) $student->bio),
                TextNormalizer::keywords((string) $student->major),
                $cvKeywords,
            ))),
            fields: $this->detectFields((string) $student->major),
            city: $this->detectCity((string) $student->location),
            hasCv: $student->cv?->parse_status === 'parsed',
        );
    }

    /** @return list<string> */
    public function detectFields(string $text): array
    {
        $normalized = TextNormalizer::normalize($text);
        $found = [];

        foreach (config('matching.fields') as $code => $field) {
            foreach ($field['terms'] as $term) {
                if (TextNormalizer::containsTerm($normalized, $term)) {
                    $found[] = $code;
                    break;
                }
            }
        }

        return $found;
    }

    public function detectCity(string $location): ?string
    {
        $normalized = TextNormalizer::normalize($location);

        foreach (config('matching.cities') as $code => $names) {
            foreach ($names as $name) {
                if (TextNormalizer::containsTerm($normalized, $name)) {
                    return $code;
                }
            }
        }

        return null;
    }
}
