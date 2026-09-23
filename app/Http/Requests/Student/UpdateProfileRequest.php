<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'school' => ['nullable', 'string', 'max:150'],
            'major' => ['nullable', 'string', 'max:150'],
            'year' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'regex:/^(\+84|0)[\d\s.-]{8,13}$/'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'họ tên',
            'school' => 'trường',
            'major' => 'ngành',
            'year' => 'năm học',
            'location' => 'khu vực',
            'phone' => 'số điện thoại',
            'bio' => 'giới thiệu',
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Số điện thoại không hợp lệ (VD: 0901 234 567).'];
    }
}
