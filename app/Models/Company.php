<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    /** Một công ty có nhiều tin tuyển (bảng job_posts). */
    public function jobPosts(): HasMany
    {
        return $this->hasMany(JobPost::class);
    }
}
