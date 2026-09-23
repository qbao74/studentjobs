<?php

namespace App\Models;

use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'aliases'])]
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    public function jobPosts(): BelongsToMany
    {
        return $this->belongsToMany(JobPost::class, 'job_skill')->withPivot('is_required');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'student_skill')->withPivot('source');
    }

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
        ];
    }
}
