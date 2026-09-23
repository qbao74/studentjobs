<?php

namespace Database\Seeders;

use App\Services\Matching\RecommendationService;
use App\Services\Profile\ProfileScoreCalculator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Laravel bắt buộc tên run() — điểm vào khi gõ php artisan db:seed.
     */
    public function run(): void
    {
        $this->call([
            JobPlatformSeeder::class,
        ]);

        // Điểm khớp là dữ liệu suy ra, nên tính bằng đúng pipeline thật thay vì viết tay.
        app(RecommendationService::class)->refreshAll();
        app(ProfileScoreCalculator::class)->refreshAll();
    }
}
