<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class JobPost extends Model
{
    /** Việc này thuộc 1 công ty. */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
    /** Kỹ năng của tin tuyển — bảng trung gian job_skill. */
    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skill')->withTimestamps();
    }
    /** JSON trong DB → mảng PHP khi đọc. */
    protected function casts(): array
    {
        return [
            'requirements' => 'array',
            'benefits' => 'array',
        ];
    }
}
