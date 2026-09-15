<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cv extends Model
{
    protected $fillable = [
        'student_id',
        'title',
        'file_path',
        'is_default',
    ];

    // 1 CV thuộc về 1 Student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // 1 CV có thể được dùng cho nhiều đơn ứng tuyển
    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}