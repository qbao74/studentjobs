<?php

namespace App\Http\Requests\Employer;

use App\Enums\Role;
use Illuminate\Foundation\Http\FormRequest;

class RecruiterNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(Role::Employer) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['body' => 'ghi chú'];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('body')) {
            $this->merge(['body' => trim((string) $this->input('body'))]);
        }
    }
}
