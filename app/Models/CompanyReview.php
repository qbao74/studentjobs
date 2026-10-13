<?php

namespace App\Models;

use Database\Factories\CompanyReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'student_id', 'application_id', 'rating', 'comment'])]
class CompanyReview extends Model
{
    /** @use HasFactory<CompanyReviewFactory> */
    use HasFactory;

    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }
}
