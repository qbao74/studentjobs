<?php

namespace App\Models;

use App\Enums\SupportCategory;
use App\Enums\SupportTicketStatus;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'category', 'subject', 'status'])]
class SupportTicket extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportReply::class);
    }

    public function latestReply(): HasOne
    {
        return $this->hasOne(SupportReply::class)->latestOfMany();
    }

    public function isClosed(): bool
    {
        return $this->status === SupportTicketStatus::Closed;
    }

    protected function casts(): array
    {
        return [
            'category' => SupportCategory::class,
            'status' => SupportTicketStatus::class,
        ];
    }
}
