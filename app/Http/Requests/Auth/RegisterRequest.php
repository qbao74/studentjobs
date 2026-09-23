<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/** Dùng chung cho sinh viên và nhà tuyển dụng; route employer thêm các trường công ty. */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function isEmployer(): bool
    {
        return $this->routeIs('register.employer*');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];

        if ($this->isEmployer()) {
            $rules += [
                'company_name' => ['required', 'string', 'max:150'],
                'company_location' => ['nullable', 'string', 'max:150'],
                'position' => ['nullable', 'string', 'max:100'],
            ];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'name' => trim((string) $this->input('name')),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'họ tên',
            'email' => 'email',
            'password' => 'mật khẩu',
            'company_name' => 'tên công ty',
            'company_location' => 'địa chỉ công ty',
            'position' => 'chức vụ',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email này đã có tài khoản.',
            'password.confirmed' => 'Hai lần nhập mật khẩu không khớp.',
        ];
    }
}
