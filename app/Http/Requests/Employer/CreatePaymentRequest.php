<?php

namespace App\Http\Requests\Employer;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Payment::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'service_plan_id' => ['required', 'integer', 'exists:service_plans,id'],
        ];
    }

    public function attributes(): array
    {
        return ['service_plan_id' => 'gói dịch vụ'];
    }
}
