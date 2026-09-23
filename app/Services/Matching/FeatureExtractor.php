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

        $analyzed = is_array($job->analyzed) ? $job->analyzed : [];
        $fields = $this->detectFields($job->title.' '.$job->description);
        $aiField = $this->knownCode($analyzed['field'] ?? null, 'matching.fields');

        if ($aiField !== null) {
            $fields = array_values(array_unique([...$fields, $aiField]));
        }

        $notes = $this->skillNotes($analyzed['skills'] ?? []);

        return new JobRequirements(
            jobId: $job->id,
            requiredSkills: $required,
            optionalSkills: $optional,
            keywords: TextNormalizer::keywords($text),
            fields: $fields,
            city: $this->detectCity((string) $job->location) ?? $this->knownCode($analyzed['city'] ?? null, 'matching.cities'),
            isRemote: (bool) $job->is_remote,
            skillEvidence: $notes['evidence'],
        );
    }

    public function forStudent(Student $student): StudentFeatures
    {
        $student->loadMissing(['skills', 'cv']);

        $parsed = $student->cv?->parse_status === 'parsed' && is_array($student->cv->parsed)
            ? $student->cv->parsed
            : [];
        $fields = $this->detectFields((string) $student->major);
        $aiField = $this->knownCode($parsed['field'] ?? null, 'matching.fields');

        if ($aiField !== null) {
            $fields = array_values(array_unique([...$fields, $aiField]));
        }

        $notes = $this->skillNotes($parsed['skills'] ?? []);

        return new StudentFeatures(
            studentId: $student->id,
            skills: $student->skills->pluck('name', 'id')->all(),
            keywords: array_values(array_unique(array_merge(
                TextNormalizer::keywords((string) $student->bio),
                TextNormalizer::keywords((string) $student->major),
                is_array($parsed['keywords'] ?? null) ? $parsed['keywords'] : [],
            ))),
            fields: $fields,
            city: $this->detectCity((string) $student->location) ?? $this->knownCode($parsed['city'] ?? null, 'matching.cities'),
            hasCv: $student->cv?->parse_status === 'parsed',
            skillEvidence: $notes['evidence'],
            skillImportance: $notes['importance'],
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

    private function knownCode(mixed $code, string $configKey): ?string
    {
        if (! is_string($code) || ! array_key_exists($code, config($configKey))) {
            return null;
        }

        return $code;
    }

    /**
     * @return array{evidence: array<int, string>, importance: array<int, string>}
     */
    private function skillNotes(mixed $skills): array
    {
        $evidence = [];
        $importance = [];

        if (! is_array($skills)) {
            return ['evidence' => [], 'importance' => []];
        }

        foreach ($skills as $skill) {
            if (! is_array($skill) || ! isset($skill['id'])) {
                continue;
            }

            $id = (int) $skill['id'];

            if (is_string($skill['evidence'] ?? null) && $skill['evidence'] !== '') {
                $evidence[$id] = $skill['evidence'];
            }

            if (is_string($skill['importance'] ?? null) && $skill['importance'] !== '') {
                $importance[$id] = $skill['importance'];
            }
        }

        return ['evidence' => $evidence, 'importance' => $importance];
    }
}
