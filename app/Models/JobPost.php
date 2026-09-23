<?php

namespace App\Models;

use App\Enums\JobStatus;
use Database\Factories\JobPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'salary', 'location', 'is_remote', 'type', 'hours', 'image', 'description', 'requirements', 'benefits', 'analyzed', 'status'])]
class JobPost extends Model
{
    /** @use HasFactory<JobPostFactory> */
    use HasFactory;

    /** Việc này thuộc 1 công ty. */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Kỹ năng của tin tuyển — bảng trung gian job_skill. */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skill')
            ->withPivot('is_required')
            ->withTimestamps();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(JobRecommendation::class);
    }

    /** Tin sinh viên được thấy: đang mở. */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->where('status', JobStatus::Open);
    }

    /** JSON trong DB → mảng PHP khi đọc. */
    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'benefits' => 'array',
            'analyzed' => 'array',
            'is_remote' => 'boolean',
            'status' => JobStatus::class,
        ];
    }
}
