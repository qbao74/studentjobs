<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Payment;
use App\Models\ServicePlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plan = ServicePlan::query()->where('code', 'basic')->first() ?? ServicePlan::factory()->create();

        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory()->employer(),
            'service_plan_id' => $plan->id,
            'amount' => $plan->price_amount,
            'currency' => 'VND',
            'reference' => 'PAY-'.now()->format('Ymd').'-'.fake()->unique()->numerify('######'),
            'status' => PaymentStatus::Pending,
            'provider' => 'simulation',
            'expires_at' => now()->addHours(24),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'provider_txn_id' => 'sim-'.$attributes['reference'],
            'starts_on' => now(),
            'ends_on' => now()->addDays(30),
        ]);
    }
}
