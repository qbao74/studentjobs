<?php

namespace App\Models;

use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'school', 'major', 'year', 'location', 'phone', 'bio', 'avatar', 'profile_score'])]
class Student extends Model
{
    /** @use HasFactory<StudentFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Kỹ năng — pivot `source` cho biết tự chọn (manual) hay đọc từ CV (cv). */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'student_skill')
            ->withPivot('source')
            ->withTimestamps();
    }

    public function cv(): HasOne
    {
        return $this->hasOne(Cv::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(JobRecommendation::class);
    }

    public function savedJobs(): BelongsToMany
    {
        return $this->belongsToMany(JobPost::class, 'saved_jobs')->withTimestamps();
    }

    public function followedCompanies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_follows')->withTimestamps();
    }
}
