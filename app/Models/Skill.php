<?php

namespace App\Models;

use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

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

    /** So tên không phân biệt hoa thường: "php" dùng lại kỹ năng "PHP" đã có, chưa có thì tạo mới. */
    public static function findOrCreateByName(string $name): self
    {
        $name = Str::squish($name);

        return static::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first()
            ?? static::create(['name' => $name, 'aliases' => []]);
    }

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
        ];
    }
}
