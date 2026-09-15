<?php

namespace Database\Seeders;

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
    }
}
