<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'student_id',
        'job_id',
        'cv_id',
        'cover_letter',
        'status',
    ];

    // 1 đơn ứng tuyển thuộc về 1 Student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // 1 đơn ứng tuyển thuộc về 1 Job
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    // 1 đơn ứng tuyển sử dụng 1 CV
    public function cv()
    {
        return $this->belongsTo(Cv::class);
    }
}