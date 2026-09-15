<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    protected $table = 'job_posts'; // Quan trọng: trỏ đến bảng mới

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'salary',
        'location',
        'deadline',
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}