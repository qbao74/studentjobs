<?php

namespace App\Http\Requests\Employer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

/**
 * Form tạo / sửa tin. Yêu cầu, quyền lợi nhập mỗi dòng một ý; kỹ năng nhập cách nhau bằng dấu phẩy.
 * jobData() đổi dữ liệu form thành đúng dạng JobPostService cần.
 */
class JobPostRequest extends FormRequest
{
    public const TYPES = ['Part-time', 'Thực tập', 'Full-time', 'Freelance'];

    private const MAX_SKILLS = 15;

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
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:'.implode(',', self::TYPES)],
            'salary' => ['nullable', 'string', 'max:100'],
            'is_remote' => ['boolean'],
            'location' => ['required_unless:is_remote,1', 'nullable', 'string', 'max:150'],
            'hours' => ['nullable', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:5000'],
            'requirements' => ['nullable', 'string', 'max:3000'],
            'benefits' => ['nullable', 'string', 'max:3000'],
            'required_skills' => ['nullable', 'string', 'max:500'],
            'optional_skills' => ['nullable', 'string', 'max:500'],
            'image' => ['nullable', 'url:https', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'tên vị trí',
            'type' => 'hình thức',
            'salary' => 'mức lương',
            'is_remote' => 'làm từ xa',
            'location' => 'địa điểm',
            'hours' => 'thời gian làm',
            'description' => 'mô tả công việc',
            'requirements' => 'yêu cầu',
            'benefits' => 'quyền lợi',
            'required_skills' => 'kỹ năng bắt buộc',
            'optional_skills' => 'kỹ năng điểm cộng',
            'image' => 'ảnh bìa',
        ];
    }

    public function messages(): array
    {
        return ['location.required_unless' => 'Nhập địa điểm, hoặc chọn "Làm từ xa".'];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $skills = [...$this->skillList('required_skills'), ...$this->skillList('optional_skills')];

                if ($skills === []) {
                    $validator->errors()->add('required_skills', 'Nhập ít nhất một kỹ năng để hệ thống so khớp ứng viên.');
                }
                if (count($skills) > self::MAX_SKILLS) {
                    $validator->errors()->add('required_skills', 'Tối đa '.self::MAX_SKILLS.' kỹ năng cho một tin.');
                }
                foreach ($skills as $skill) {
                    if (mb_strlen($skill) > 50) {
                        $validator->errors()->add('required_skills', "Tên kỹ năng \"{$skill}\" quá dài (tối đa 50 ký tự).");
                    }
                }
            },
        ];
    }

    /**
     * @return array{fields: array<string, mixed>, skills: array<string, bool>}
     */
    public function jobData(): array
    {
        $data = $this->validated();
        $remote = (bool) ($data['is_remote'] ?? false);

        // Kỹ năng có ở cả hai ô thì tính là bắt buộc, giữ cách viết ở ô bắt buộc.
        $skills = array_fill_keys($this->skillList('required_skills'), true);
        $taken = array_map(Str::lower(...), array_keys($skills));
        foreach ($this->skillList('optional_skills') as $name) {
            if (! in_array(Str::lower($name), $taken, true)) {
                $skills[$name] = false;
            }
        }

        return [
            'fields' => [
                'title' => $data['title'],
                'type' => $data['type'],
                'salary' => $data['salary'] ?? null,
                'is_remote' => $remote,
                'location' => $data['location'] ?? ($remote ? 'Remote' : null),
                'hours' => $data['hours'] ?? null,
                'description' => $data['description'],
                'requirements' => $this->lines($data['requirements'] ?? ''),
                'benefits' => $this->lines($data['benefits'] ?? ''),
                'image' => $data['image'] ?? null,
            ],
            'skills' => $skills,
        ];
    }

    /** "PHP, laravel ,  SQL" → ["PHP", "laravel", "SQL"] (bỏ trùng không phân biệt hoa thường). */
    private function skillList(string $key): array
    {
        return collect(explode(',', (string) $this->input($key)))
            ->map(fn ($s) => Str::squish($s))
            ->filter()
            ->unique(fn ($s) => Str::lower($s))
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function lines(string $text): array
    {
        return collect(preg_split('/\R/', $text))
            ->map(fn ($l) => Str::squish(ltrim($l, "-•* \t")))
            ->filter()
            ->values()
            ->all();
    }
}
