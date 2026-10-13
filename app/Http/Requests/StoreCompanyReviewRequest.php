<?php

namespace App\Http\Requests;

use App\Models\CompanyReview;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CompanyReview::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:'.CompanyReview::MIN_RATING, 'max:'.CompanyReview::MAX_RATING],
            'comment' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rating' => 'điểm đánh giá',
            'comment' => 'nhận xét',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('comment')) {
            $this->merge(['comment' => trim((string) $this->input('comment'))]);
        }
    }
}
