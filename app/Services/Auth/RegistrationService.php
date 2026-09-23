<?php

namespace App\Services\Auth;

use App\Enums\Role;
use App\Models\Company;
use App\Models\Employer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Tạo tài khoản cùng hồ sơ đi kèm trong một transaction:
 * lỗi ở bước nào thì không bảng nào bị ghi dở.
 */
class RegistrationService
{
    /** Màu logo mặc định cho công ty mới, chọn theo tên để mỗi công ty một màu cố định. */
    private const COLORS = ['#7C5CFF', '#2563EB', '#EA580C', '#059669', '#DB2777', '#0891B2', '#CA8A04', '#DC2626'];

    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function registerStudent(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->createUser($data, Role::Student);

            Student::create(['user_id' => $user->id]);

            return $user;
        });
    }

    /**
     * @param  array{name: string, email: string, password: string, company_name: string, company_location?: string|null, position?: string|null}  $data
     */
    public function registerEmployer(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->createUser($data, Role::Employer);

            $company = new Company([
                'name' => $data['company_name'],
                'location' => $data['company_location'] ?? null,
                'slug' => $this->uniqueSlug($data['company_name']),
                'initial' => Str::upper(Str::substr(Str::ascii($data['company_name']), 0, 1)),
                'color' => self::COLORS[crc32($data['company_name']) % count(self::COLORS)],
            ]);
            $company->save();

            Employer::create([
                'user_id' => $user->id,
                'company_id' => $company->id,
                'position' => $data['position'] ?? null,
            ]);

            return $user;
        });
    }

    private function createUser(array $data, Role $role): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
            'role' => $role,
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'cong-ty';
        $slug = $base;
        $i = 2;

        while (Company::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
