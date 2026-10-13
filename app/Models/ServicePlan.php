<?php

namespace App\Models;

use Database\Factories\ServicePlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'description', 'price_amount', 'duration_days', 'max_open_jobs', 'is_active'])]
class ServicePlan extends Model
{
    /** @use HasFactory<ServicePlanFactory> */
    use HasFactory;

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function formattedPrice(): string
    {
        return number_format($this->price_amount, 0, ',', '.').' '.config('billing.currency', 'VND');
    }

    public function jobLimitLabel(): string
    {
        return $this->max_open_jobs === null
            ? 'Không giới hạn tin đang mở'
            : 'Tối đa '.$this->max_open_jobs.' tin đang mở';
    }

    /** @param  Builder<ServicePlan>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    protected function casts(): array
    {
        return [
            'price_amount' => 'integer',
            'duration_days' => 'integer',
            'max_open_jobs' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
