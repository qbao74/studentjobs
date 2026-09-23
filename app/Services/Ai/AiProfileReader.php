<?php

namespace App\Services\Ai;

use App\Models\Skill;
use App\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Gửi chữ CV hoặc tin tuyển cho mô hình và chỉ giữ những mục có câu dẫn chiếu nằm trong chữ gốc.
 */
class AiProfileReader
{
    /** @var Collection<int, Skill>|null */
    private ?Collection $catalog = null;

    /** @var array<string, int> */
    private const IMPORTANCE_RANK = [
        'required' => 2,
        'demonstrated' => 2,
        'optional' => 1,
        'listed' => 1,
    ];

    public function read(string $text, string $side): ProfileDocument
    {
        $allowed = match ($side) {
            'cv' => ['listed', 'demonstrated'],
            'job' => ['required', 'optional'],
            default => throw new InvalidArgumentException('Không rõ phía cần đọc.'),
        };

        $response = Http::withToken((string) config('ai.key'))
            ->timeout((int) config('ai.timeout'))
            ->acceptJson()
            ->post((string) config('ai.url'), [
                'model' => config('ai.model'),
                'temperature' => 0,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->instructions($side)],
                    ['role' => 'user', 'content' => mb_substr($text, 0, 12000)],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('AI không đọc được văn bản ('.$response->status().').');
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content)) {
            throw new RuntimeException('AI trả về dữ liệu không đọc được.');
        }

        return $this->document($this->decode($content), $text, $allowed);
    }

    private function instructions(string $side): string
    {
        $importance = $side === 'job'
            ? 'required (bắt buộc) hoặc optional (chỉ là lợi thế)'
            : 'listed (chỉ nêu tên) hoặc demonstrated (có việc làm hoặc dự án chứng minh)';

        return <<<TEXT
        Bạn đọc một đoạn văn và trả về đúng một JSON, không markdown, không chấm điểm.
        Chỉ ghi điều có căn cứ trong đoạn. evidence là cụm nguyên văn cắt từ đoạn đó.
        importance của mỗi kỹ năng: {$importance}.
        name là tên kỹ năng ngắn (Laravel, React, Git), không phải cả câu.
        field chỉ được là it, design, marketing, data hoặc null.
        city chỉ được là hcm, hn, dn, ct hoặc null.
        Không thấy thì để mảng rỗng hoặc null.
        {$this->catalogLine()}
        JSON có các khóa: skills (name, importance, evidence), field, city, remote, experience (role, months, evidence).
        TEXT;
    }

    private function catalogLine(): string
    {
        $names = Skill::query()->orderBy('name')->limit(200)->pluck('name');

        if ($names->isEmpty()) {
            return 'Danh mục kỹ năng đang trống. Hãy dùng tên ngắn, phổ biến.';
        }

        return 'Nếu trùng danh mục thì dùng đúng tên sau: '.$names->implode(', ').'.';
    }

    /** @return array<string, mixed> */
    private function decode(string $content): array
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('AI trả về dữ liệu không đọc được.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $allowed
     */
    private function document(array $payload, string $source, array $allowed): ProfileDocument
    {
        return new ProfileDocument(
            skills: $this->skills($payload['skills'] ?? [], $source, $allowed),
            field: $this->code($payload['field'] ?? null, 'matching.fields'),
            city: $this->groundedCity($payload['city'] ?? null, $source),
            remote: $this->groundedRemote($payload['remote'] ?? false, $source),
            experience: $this->experience($payload['experience'] ?? [], $source),
        );
    }

    /**
     * @param  list<string>  $allowed
     * @return list<array{id: int, name: string, importance: string, evidence: string}>
     */
    private function skills(mixed $rows, string $source, array $allowed): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $kept = [];

        foreach (array_slice($rows, 0, 20) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = Str::squish((string) ($row['name'] ?? ''));
            $importance = Str::lower(trim((string) ($row['importance'] ?? '')));
            $evidence = $this->quote((string) ($row['evidence'] ?? ''), $source);
            $words = preg_split('/\s+/u', $name) ?: [];

            if ($evidence === null || ! in_array($importance, $allowed, true) || mb_strlen($name) < 2 || mb_strlen($name) > 60 || count($words) > 4) {
                continue;
            }

            $skill = $this->resolveSkill($name);
            $current = $kept[$skill->id] ?? null;

            if ($current === null || self::IMPORTANCE_RANK[$importance] > self::IMPORTANCE_RANK[$current['importance']]) {
                $kept[$skill->id] = ['id' => $skill->id, 'name' => $skill->name, 'importance' => $importance, 'evidence' => $evidence];
            }
        }

        return array_values($kept);
    }

    /**
     * @return list<array{role: string, months: int|null, evidence: string}>
     */
    private function experience(mixed $rows, string $source): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $kept = [];

        foreach (array_slice($rows, 0, 8) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $role = Str::squish((string) ($row['role'] ?? ''));
            $evidence = $this->quote((string) ($row['evidence'] ?? ''), $source);

            if ($evidence === null || $role === '' || mb_strlen($role) > 120) {
                continue;
            }

            $months = $row['months'] ?? null;
            $months = is_numeric($months) ? (int) $months : null;

            if ($months !== null && ($months < 0 || $months > 600)) {
                $months = null;
            }

            $kept[] = ['role' => $role, 'months' => $months, 'evidence' => $evidence];
        }

        return $kept;
    }

    private function quote(string $evidence, string $source): ?string
    {
        $evidence = trim($evidence, " \t\n\r\"“”");

        if (mb_strlen($evidence) < 2 || mb_strlen($evidence) > 180) {
            return null;
        }

        if (mb_stripos($source, $evidence) === false) {
            return null;
        }

        return $evidence;
    }

    /** Thành phố và remote chỉ giữ khi chữ gốc thật sự nói tới, không nhận chỗ mô hình tự điền. */
    private function groundedCity(mixed $value, string $source): ?string
    {
        $code = $this->code($value, 'matching.cities');

        if ($code === null) {
            return null;
        }

        $normalized = TextNormalizer::normalize($source);

        foreach (config('matching.cities')[$code] as $name) {
            if (TextNormalizer::containsTerm($normalized, $name)) {
                return $code;
            }
        }

        return null;
    }

    private function groundedRemote(mixed $value, string $source): bool
    {
        if (! filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $normalized = TextNormalizer::normalize($source);

        foreach (['remote', 'tu xa', 'lam tu xa', 'work from home'] as $term) {
            if (TextNormalizer::containsTerm($normalized, $term)) {
                return true;
            }
        }

        return false;
    }

    private function code(mixed $value, string $configKey): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $code = Str::lower(trim($value));

        return array_key_exists($code, config($configKey)) ? $code : null;
    }

    private function resolveSkill(string $name): Skill
    {
        $needle = Str::lower($name);

        foreach ($this->catalog() as $skill) {
            foreach (array_merge([$skill->name], $skill->aliases ?? []) as $alias) {
                if (Str::lower((string) $alias) === $needle) {
                    return $skill;
                }
            }
        }

        $created = Skill::findOrCreateByName($name);
        $this->catalog()->push($created);

        return $created;
    }

    /** @return Collection<int, Skill> */
    private function catalog(): Collection
    {
        return $this->catalog ??= Skill::query()->get(['id', 'name', 'aliases']);
    }
}
