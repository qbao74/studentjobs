<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'original_name', 'path', 'mime_type', 'size', 'extracted_text', 'parsed', 'parse_status', 'parse_error'])]
#[Hidden(['path', 'extracted_text'])]
class Cv extends Model
{
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    protected function casts(): array
    {
        return [
            'parsed' => 'array',
            'size' => 'integer',
        ];
    }
}
