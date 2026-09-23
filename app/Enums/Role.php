<?php

namespace App\Enums;

enum Role: string
{
    case Student = 'student';
    case Employer = 'employer';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Sinh viên',
            self::Employer => 'Nhà tuyển dụng',
            self::Admin => 'Quản trị viên',
        };
    }

    /** Trang đầu tiên của mỗi vai trò sau khi đăng nhập. */
    public function homePath(): string
    {
        return match ($this) {
            self::Student => '/',
            self::Employer => '/employer',
            self::Admin => '/admin',
        };
    }
}
