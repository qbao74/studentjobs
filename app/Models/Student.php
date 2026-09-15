<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    // Cho phép gán dữ liệu hàng loạt vào các cột này
    protected $fillable = [
        'user_id',
        'full_name',
        'phone',
        'university',
        'major',
        'graduation_year',
        'bio',
        'avatar',
    ];

    // Mối quan hệ: 1 Student thuộc về 1 User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Mối quan hệ: 1 Student có nhiều CV
    public function cvs()
    {
        return $this->hasMany(Cv::class);
    }

    // Mối quan hệ: 1 Student có nhiều đơn ứng tuyển
    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}