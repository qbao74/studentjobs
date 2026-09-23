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
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => ['verified' => true]);
    }
}
