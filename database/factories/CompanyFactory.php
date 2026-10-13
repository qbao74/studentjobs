<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'name' => $name,
            'verified' => false,
            'tagline' => 'Công ty công nghệ',
            'size' => '50–200 nhân sự',
            'location' => 'Quận 1, TP.HCM',
            'color' => '#2563EB',
            'initial' => Str::upper(Str::substr($name, 0, 1)),
            'about' => fake()->sentence(12),
            'subscription_starts_at' => now(),
            'subscription_ends_at' => now()->addDays(30),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => ['verified' => true]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_starts_at' => now()->subDays(40),
            'subscription_ends_at' => now()->subDay(),
        ]);
    }

    public function withoutSubscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'service_plan_id' => null,
            'subscription_starts_at' => null,
            'subscription_ends_at' => null,
        ]);
    }
}
