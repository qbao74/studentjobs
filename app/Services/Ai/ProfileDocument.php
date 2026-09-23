<?php

namespace App\Services\Ai;

/** CV hoặc tin tuyển sau khi AI đọc: cùng một dạng, đã gắn id kỹ năng. */
final readonly class ProfileDocument
{
    /**
     * @param  list<array{id: int, name: string, importance: string, evidence: string}>  $skills
     * @param  list<array{role: string, months: int|null, evidence: string}>  $experience
     */
    public function __construct(
        public array $skills,
        public ?string $field,
        public ?string $city,
        public bool $remote,
        public array $experience,
    ) {}

    /**
     * @return array{skills: list<array{id: int, name: string, importance: string, evidence: string}>, field: ?string, city: ?string, remote: bool, experience: list<array{role: string, months: int|null, evidence: string}>}
     */
    public function toArray(): array
    {
        return [
            'skills' => $this->skills,
            'field' => $this->field,
            'city' => $this->city,
            'remote' => $this->remote,
            'experience' => $this->experience,
        ];
    }
}
