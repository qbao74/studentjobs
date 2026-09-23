<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'tagline', 'size', 'location', 'color', 'initial', 'about'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /** Một công ty có nhiều tin tuyển (bảng job_posts). */
    public function jobPosts(): HasMany
    {
        return $this->hasMany(JobPost::class);
    }

    public function employers(): HasMany
    {
        return $this->hasMany(Employer::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'company_follows')->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'rating' => 'float',
        ];
    }
}
