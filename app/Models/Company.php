<?php

namespace App\Models;

use App\Enums\JobStatus;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'tagline', 'size', 'location', 'color', 'initial', 'about', 'verified', 'service_plan_id', 'subscription_starts_at', 'subscription_ends_at'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /** Một công ty có nhiều tin tuyển (bảng job_posts). */
    public function jobPosts(): HasMany
    {
        return $this->hasMany(JobPost::class);
    }

    public function employers(): HasMany
    {
        return $this->hasMany(Employer::class);
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'company_follows')->withTimestamps();
    }

    public function servicePlan(): BelongsTo
    {
        return $this->belongsTo(ServicePlan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class);
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscription_ends_at !== null && $this->subscription_ends_at->isFuture();
    }

    public function isSubscriptionExpiringSoon(): bool
    {
        if (! $this->hasActiveSubscription()) {
            return false;
        }

        return $this->subscription_ends_at->lte(now()->addDays((int) config('billing.expiring_soon_days', 7)));
    }

    /** active | expiring | expired | none */
    public function subscriptionState(): string
    {
        if ($this->subscription_ends_at === null) {
            return 'none';
        }

        if ($this->subscription_ends_at->isPast()) {
            return 'expired';
        }

        return $this->isSubscriptionExpiringSoon() ? 'expiring' : 'active';
    }

    public function subscriptionStateLabel(): string
    {
        return match ($this->subscriptionState()) {
            'active' => 'Đang hoạt động',
            'expiring' => 'Sắp hết hạn',
            'expired' => 'Đã hết hạn',
            default => 'Chưa thanh toán',
        };
    }

    /** null = không giới hạn. */
    public function remainingOpenJobSlots(): ?int
    {
        $limit = $this->servicePlan?->max_open_jobs;
        if ($limit === null) {
            return null;
        }

        $open = $this->jobPosts()->where('status', JobStatus::Open)->count();

        return max(0, $limit - $open);
    }

    public function canActivateAnotherJob(): bool
    {
        if (! $this->hasActiveSubscription()) {
            return false;
        }

        $remaining = $this->remainingOpenJobSlots();

        return $remaining === null || $remaining > 0;
    }

    protected function casts(): array
    {
        return [
            'verified' => 'boolean',
            'rating' => 'float',
            'subscription_starts_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }
}
